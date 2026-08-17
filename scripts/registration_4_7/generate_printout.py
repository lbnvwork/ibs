"""Генератор PDF-распечатки фрагментов исходного кода для регистрации программы для ЭВМ.

Требования к формату (из постановки задачи 4.7):
  * формат страницы A4, ориентация книжная;
  * поля: верхнее/правое/нижнее 20 мм, левое 25 мм;
  * шрифт 12 pt;
  * нумерация страниц начинается с 2 (первая страница — титульный лист —
    оформляется патентоведом и не входит в распечатку).

Использование:
    python generate_printout.py --manifest docs/registration_4_7/object_a/manifest.yaml \
        --out docs/registration_4_7/object_a/printout/printout.pdf
"""
from __future__ import annotations

import argparse
import json
import os
import sys
from typing import Iterable

from reportlab.lib.colors import black, grey
from reportlab.lib.pagesizes import A4
from reportlab.lib.units import mm
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.pdfgen import canvas

import common

# Моноширинный шрифт с поддержкой кириллицы (в коде есть русские комментарии).
_MONO_FONT = "DejaVuSansMono"
_MONO_FONT_BOLD = "DejaVuSansMono-Bold"

_FONT_PATHS = [
    "/usr/share/fonts/truetype/dejavu/DejaVuSansMono.ttf",
    "/usr/share/fonts/truetype/dejavu/DejaVuSansMono-Bold.ttf",
]


def _register_fonts() -> None:
    for path in _FONT_PATHS:
        if not os.path.isfile(path):
            print(f"[error] шрифт не найден: {path}", file=sys.stderr)
            sys.exit(1)
    pdfmetrics.registerFont(TTFont(_MONO_FONT, _FONT_PATHS[0]))
    pdfmetrics.registerFont(TTFont(_MONO_FONT_BOLD, _FONT_PATHS[1]))


def _margin_pt(value) -> float:
    """Преобразует значение поля (например, '20 мм' или 20) в пункты."""
    if isinstance(value, (int, float)):
        return float(value)
    text = str(value).strip().lower().replace(",", ".")
    parts = text.split()
    number = float(parts[0])
    unit = parts[1] if len(parts) > 1 else "pt"
    if unit.startswith("мм") or unit.startswith("mm"):
        return number * mm
    if unit.startswith("см") or unit.startswith("cm"):
        return number * 10 * mm
    return number  # уже в pt


def _wrap_line(line: str, chars_per_line: int, indent: str) -> list:
    """Переносит длинную строку кода по границе ``chars_per_line`` символов.

    Продолжение строки получает отступ ``indent`` для визуального отделения.
    """
    if len(line) <= chars_per_line:
        return [line]
    chunks = [line[:chars_per_line]]
    rest = line[chars_per_line:]
    sub_limit = max(1, chars_per_line - len(indent))
    while rest:
        chunks.append(indent + rest[:sub_limit])
        rest = rest[sub_limit:]
    return chunks


def _lines_per_page(manifest: dict) -> int:
    fmt = manifest["printout"]["format"]
    font_size = float(str(fmt["font_size"]).replace("pt", "").strip())
    margins = fmt["margins"]
    top = _margin_pt(margins["top"])
    bottom = _margin_pt(margins["bottom"])
    usable_height = A4[1] - top - bottom
    leading = font_size * 1.2
    return int(usable_height / leading)


def _estimate_lines(file_path: str, manifest: dict) -> int:
    """Оценка числа строк, которое файл займёт в распечатке (с переносами)."""
    fmt = manifest["printout"]["format"]
    font_size = float(str(fmt["font_size"]).replace("pt", "").strip())
    char_width = pdfmetrics.stringWidth("M", _MONO_FONT, font_size)
    margins = fmt["margins"]
    usable_width = A4[0] - _margin_pt(margins["left"]) - _margin_pt(margins["right"])
    chars_per_line = max(1, int(usable_width / char_width))
    total = 1  # заголовок файла
    try:
        with open(file_path, encoding="utf-8", errors="replace") as fh:
            for line in fh:
                total += len(_wrap_line(line.rstrip("\n"), chars_per_line, "    "))
    except OSError:
        return 1
    return total


def select_files(
    files: list,
    manifest: dict,
) -> tuple:
    """Выбирает файлы согласно стратегии отбора из манифеста.

    Возвращает ``(included, excluded)``. Стратегия ``all`` включает все файлы
    (упорядочивая по приоритету для читаемости), ``fragments`` — отбирает
    фрагменты по приоритету до исчерпания бюджета страниц.
    """
    selection = manifest.get("printout", {}).get("selection", {})
    strategy = selection.get("strategy", "all")
    priority = selection.get("priority", [])

    ordered = sorted(files, key=lambda p: (common.selection_priority(p[1], priority), p[1]))

    if strategy != "fragments":
        return ordered, []

    budget_pages = int(manifest["printout"]["target_max_pages"])
    budget_lines = budget_pages * _lines_per_page(manifest)

    included = []
    excluded = []
    used_lines = 0

    for file_path, rel_path in ordered:
        estimate = _estimate_lines(file_path, manifest)
        if included and used_lines + estimate > budget_lines:
            excluded.append((file_path, rel_path))
            continue
        included.append((file_path, rel_path))
        used_lines += estimate

    return included, excluded


class PrintoutBuilder:
    def __init__(self, manifest: dict, out_path: str) -> None:
        self.manifest = manifest
        self.out_path = out_path
        fmt = manifest["printout"]["format"]
        self.font_size = float(str(fmt["font_size"]).replace("pt", "").strip())
        self.leading = self.font_size * 1.2
        margins = fmt["margins"]
        self.left = _margin_pt(margins["left"])
        self.right = _margin_pt(margins["right"])
        self.top = _margin_pt(margins["top"])
        self.bottom = _margin_pt(margins["bottom"])
        self.usable_width = A4[0] - self.left - self.right
        self.char_width = pdfmetrics.stringWidth("M", _MONO_FONT, self.font_size)
        self.chars_per_line = max(1, int(self.usable_width / self.char_width))
        self.numbering_start = int(fmt.get("page_numbering", {}).get("start_from", 2))

        self.canvas = canvas.Canvas(out_path, pagesize=A4)
        self.page_count = 0
        self.current_y = A4[1] - self.top

    def _new_page(self) -> None:
        self.page_count += 1
        self.current_y = A4[1] - self.top

    def _footer(self) -> None:
        """Печатает номер страницы по центру нижнего поля.

        Нумерация начинается с ``numbering_start`` (2): первая страница
        распечатки соответствует титульному листу (стр. 1), который оформляет
        патентовед, поэтому первая кодовая страница имеет номер 2.
        """
        page_number = self.numbering_start + self.page_count - 1
        if page_number < self.numbering_start:
            return
        self.canvas.setFont(_MONO_FONT, self.font_size)
        self.canvas.setFillColor(black)
        self.canvas.drawCentredString(A4[0] / 2.0, self.bottom / 2.0, str(page_number))

    def _ensure_space(self, needed_lines: int) -> None:
        """Переходит на новую страницу, если не хватает места на ``needed_lines`` строк."""
        needed = needed_lines * self.leading
        if self.current_y - needed < self.bottom:
            self._footer()
            self.canvas.showPage()
            self._new_page()

    def _draw_line(self, text: str, font: str, color=black, indent: float = 0.0) -> None:
        self.canvas.setFont(font, self.font_size)
        self.canvas.setFillColor(color)
        self.canvas.drawString(self.left + indent, self.current_y, text)
        self.current_y -= self.leading

    def draw_title_page(self) -> None:
        """Печатает титульный лист-заглушку (стр. 1, без номера страницы)."""
        obj = self.manifest.get("object", {})
        title = obj.get("working_title") or obj.get("official_title") or "(без названия)"
        c = self.canvas
        c.setTitle(title)
        c.setFont(_MONO_FONT_BOLD, 14)
        c.drawCentredString(A4[0] / 2.0, A4[1] - 80 * mm, title)
        c.setFont(_MONO_FONT, 12)
        c.setFillColor(grey)
        lines = [
            "Распечатка фрагментов исходного кода",
            "(титульный лист оформляется патентоведом)",
        ]
        y = A4[1] - 100 * mm
        for line in lines:
            c.drawCentredString(A4[0] / 2.0, y, line)
            y -= 16
        self._footer()
        c.showPage()
        self._new_page()

    def draw_file(self, rel_path: str, file_path: str) -> None:
        """Печатает содержимое одного файла с заголовком-путём."""
        self._ensure_space(2)
        self._draw_line(rel_path, _MONO_FONT_BOLD)
        self.current_y -= self.leading * 0.25

        try:
            with open(file_path, encoding="utf-8", errors="replace") as fh:
                for raw in fh:
                    line = raw.rstrip("\n")
                    for wrapped in _wrap_line(line, self.chars_per_line, "    "):
                        self._ensure_space(1)
                        self._draw_line(wrapped, _MONO_FONT)
        except OSError as exc:
            self._ensure_space(1)
            self._draw_line(f"(не удалось прочитать: {exc})", _MONO_FONT, grey)

        self._ensure_space(1)

    def build(self, files: Iterable) -> None:
        self.draw_title_page()
        for file_path, rel_path in files:
            self.draw_file(rel_path, file_path)
        self._footer()
        self.canvas.showPage()
        self.page_count += 1  # финальная страница
        self.canvas.save()


def main() -> int:
    parser = argparse.ArgumentParser(description="Генератор PDF-распечатки исходного кода")
    parser.add_argument("--manifest", required=True, help="путь к manifest.yaml")
    parser.add_argument("--out", required=True, help="путь к выходному PDF")
    parser.add_argument(
        "--report",
        default=None,
        help="путь к JSON-отчёту о составе распечатки (опционально)",
    )
    args = parser.parse_args()

    manifest = common.load_manifest(args.manifest)
    _register_fonts()

    files = common.collect_files(manifest, common.repo_root())
    included, excluded = select_files(files, manifest)
    priority = manifest.get("printout", {}).get("selection", {}).get("priority", [])
    included.sort(key=lambda p: (common.selection_priority(p[1], priority), p[1]))

    out_path = args.out
    os.makedirs(os.path.dirname(out_path) or ".", exist_ok=True)
    builder = PrintoutBuilder(manifest, out_path)
    builder.build(included)

    report = {
        "object_id": manifest.get("object", {}).get("id"),
        "working_title": manifest.get("object", {}).get("working_title"),
        "total_files_included": len(included),
        "total_files_excluded": len(excluded),
        "excluded_files": [rel for _path, rel in excluded],
        "pages": builder.page_count,
        "out": out_path,
    }
    print(json.dumps(report, ensure_ascii=False, indent=2))

    if args.report:
        with open(args.report, "w", encoding="utf-8") as fh:
            json.dump(report, fh, ensure_ascii=False, indent=2)

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
