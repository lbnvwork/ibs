"""Общие утилиты для подготовки материалов регистрации программы для ЭВМ (задача 4.7).

Разделяет логику чтения манифестов и сбора файлов исходного кода между
генератором PDF-распечатки, замером объёма и проверкой.
"""
from __future__ import annotations

import fnmatch
import os
import sys
from pathlib import Path
from typing import Any, Iterable

import yaml

REPO_ROOT = Path(__file__).resolve().parents[2]


def repo_root() -> Path:
    return REPO_ROOT


def load_manifest(manifest_path: str | os.PathLike) -> dict[str, Any]:
    path = Path(manifest_path)
    with path.open(encoding="utf-8") as fh:
        return yaml.safe_load(fh) or {}


def _matches_exclude(rel_path: str, patterns: Iterable[str]) -> bool:
    base = os.path.basename(rel_path)
    return any(
        fnmatch.fnmatch(base, pat) or fnmatch.fnmatch(rel_path, pat)
        for pat in patterns
    )


def collect_files(
    manifest: dict[str, Any],
    root: str | os.PathLike | None = None,
) -> list[tuple[str, str]]:
    """Собирает файлы исходного кода согласно секции ``source`` манифеста.

    Возвращает список кортежей ``(absolute_path, relative_path)``, где
    ``relative_path`` отсчитывается от корня репозитория.
    """
    root = Path(root) if root else REPO_ROOT
    source = manifest.get("source", {})
    include_ext = [e.lower() for e in source.get("include_extensions", [])]
    exclude = source.get("exclude_patterns", [])

    files: list[tuple[str, str]] = []
    seen: set[str] = set()

    def add_file(abs_path: Path) -> None:
        rel = abs_path.relative_to(root).as_posix()
        ext = abs_path.suffix.lower()
        if include_ext and ext not in include_ext:
            return
        if _matches_exclude(rel, exclude):
            return
        key = abs_path.resolve().as_posix()
        if key in seen:
            return
        seen.add(key)
        files.append((str(abs_path), rel))

    for group in ("backend", "frontend"):
        for entry in source.get(group, []):
            entry_path = Path(entry.get("path", ""))
            abs_entry = entry_path if entry_path.is_absolute() else root / entry_path
            kind = entry.get("type", "directory")
            if kind == "file":
                if abs_entry.is_file():
                    add_file(abs_entry)
                else:
                    print(
                        f"[warn] файл из манифеста не найден: {abs_entry}",
                        file=sys.stderr,
                    )
            else:
                if not abs_entry.is_dir():
                    print(
                        f"[warn] каталог из манифеста не найден: {abs_entry}",
                        file=sys.stderr,
                    )
                    continue
                for dirpath, _dirnames, filenames in os.walk(abs_entry):
                    for filename in filenames:
                        add_file(Path(dirpath) / filename)

    return files


def selection_priority(rel_path: str, priority: Iterable[str]) -> int:
    """Возвращает индекс приоритета файла по списку glob-паттернов.

    Чем меньше значение, тем раньше файл попадает в распечатку. Файлы,
    не совпавшие ни с одним паттерном, получают приоритет ``len(priority)``.
    """
    for index, pattern in enumerate(priority):
        if fnmatch.fnmatch(rel_path, pattern):
            return index
    return len(priority)


def format_size(num_bytes: int) -> str:
    """Человекочитаемый размер в КБ/МБ/ГБ."""
    value = float(num_bytes)
    for unit in ("Б", "КБ", "МБ", "ГБ"):
        if value < 1024 or unit == "ГБ":
            if unit == "Б":
                return f"{int(value)} {unit}"
            return f"{value:.1f} {unit}"
        value /= 1024
    return f"{value:.1f} ГБ"
