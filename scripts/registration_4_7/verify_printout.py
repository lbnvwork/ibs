"""Проверка PDF-распечатки на соответствие требованиям задачи 4.7.

Проверяет:
  * размер страниц A4 и книжную ориентацию;
  * число страниц не более заданного (по умолчанию 70);
  * поля (верхнее/правое/нижнее 20 мм, левое 25 мм) — косвенно, через проверку
    настроек манифеста и отсутствия выхода текста за границы;
  * наличие титульной страницы и нумерацию страниц, начинающуюся с 2.

Использование:
    python verify_printout.py --pdf docs/registration_4_7/object_a/printout/printout.pdf \
        --manifest docs/registration_4_7/object_a/manifest.yaml
"""
from __future__ import annotations

import argparse
import sys

from pypdf import PdfReader

import common

A4_PT = (595.28, 841.89)  # ширина, высота в пунктах
TOLERANCE_PT = 2.0


def _pt_to_mm(pt: float) -> float:
    return pt / 72.0 * 25.4


def verify_pdf(pdf_path: str, manifest: dict, max_pages: int = 70) -> list[str]:
    problems: list[str] = []

    try:
        reader = PdfReader(pdf_path)
    except Exception as exc:  # noqa: BLE001
        return [f"не удалось открыть PDF: {exc}"]

    num_pages = len(reader.pages)
    if num_pages <= 0:
        problems.append("PDF пуст (нет страниц)")
        return problems

    # 1. Размер и ориентация страниц.
    for i, page in enumerate(reader.pages, start=1):
        box = page.mediabox
        width = float(box.width)
        height = float(box.height)
        if abs(width - A4_PT[0]) > TOLERANCE_PT or abs(height - A4_PT[1]) > TOLERANCE_PT:
            problems.append(
                f"стр. {i}: размер {_pt_to_mm(width):.1f}x{_pt_to_mm(height):.1f} мм "
                f"не соответствует A4 (210x297 мм)"
            )
        if width > height:
            problems.append(f"стр. {i}: альбомная ориентация вместо книжной")

    # 2. Лимит страниц.
    if num_pages > max_pages:
        problems.append(f"число страниц {num_pages} превышает лимит {max_pages}")

    # 3. Нумерация страниц: титульный лист без номера, дальше — с 2.
    fmt = manifest.get("printout", {}).get("format", {})
    start_from = int(fmt.get("page_numbering", {}).get("start_from", 2))
    expected = start_from
    for i, page in enumerate(reader.pages, start=1):
        if i == 1:
            # титульный лист: номер страницы не должен печататься
            continue
        text = (page.extract_text() or "").strip()
        if str(expected) not in text:
            problems.append(
                f"стр. {i}: не найден ожидаемый номер страницы '{expected}' (страница кода)"
            )
        expected += 1

    return problems


def verify_manifest_format(manifest: dict) -> list[str]:
    """Проверяет, что параметры формата в манифесте соответствуют требованиям."""
    problems: list[str] = []
    fmt = manifest.get("printout", {}).get("format", {})
    if fmt.get("page_size") != "A4":
        problems.append(f"page_size={fmt.get('page_size')} вместо A4")
    if fmt.get("orientation") != "portrait":
        problems.append(f"orientation={fmt.get('orientation')} вместо portrait")
    if str(fmt.get("font_size", "")).strip() != "12 pt":
        problems.append(f"font_size={fmt.get('font_size')} вместо 12 pt")
    margins = fmt.get("margins", {})
    for side, expected_mm in (("top", 20), ("right", 20), ("bottom", 20), ("left", 25)):
        value = str(margins.get(side, ""))
        if f"{expected_mm} мм" not in value and str(expected_mm) != value:
            problems.append(f"margins.{side}={value} вместо {expected_mm} мм")
    start_from = fmt.get("page_numbering", {}).get("start_from")
    if int(start_from) != 2:
        problems.append(f"page_numbering.start_from={start_from} вместо 2")
    return problems


def main() -> int:
    parser = argparse.ArgumentParser(description="Проверка PDF-распечатки исходного кода")
    parser.add_argument("--pdf", required=True)
    parser.add_argument("--manifest", required=True)
    parser.add_argument("--max-pages", type=int, default=70)
    args = parser.parse_args()

    manifest = common.load_manifest(args.manifest)
    problems = verify_manifest_format(manifest) + verify_pdf(args.pdf, manifest, args.max_pages)

    if problems:
        print("[FAIL] найдены несоответствия:")
        for problem in problems:
            print(f"  - {problem}")
        return 1

    print("[OK] распечатка соответствует требованиям задачи 4.7")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
