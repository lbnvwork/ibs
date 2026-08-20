"""Проверка реферата программы для ЭВМ (тест-кейс TC-4.7-2).

Проверяет:
  * длину аннотации (не более 700 знаков с пробелами);
  * наличие обязательных разделов: назначение, область применения,
    функциональные возможности, тип ЭВМ, язык программирования, ОС,
    объём программы.

Использование:
    python verify_abstract.py --abstract docs/registration_4_7/object_a/abstract.md
"""
from __future__ import annotations

import argparse
import re
import sys

MAX_ANNOTATION_CHARS = 700

REQUIRED_SECTIONS = [
    "## Аннотация",
    "## Тип реализующей ЭВМ",
    "## Язык программирования",
    "## Вид и версия операционной системы",
    "## Объём программы в машиночитаемой форме",
]

ANNOTATION_KEYWORDS = {
    "назначение": ["назначение", "предназначен"],
    "область применения": ["область применения", "примен"],
    "функциональные возможности": ["функциональные возможности", "возможности"],
}


def verify_abstract(path: str) -> list[str]:
    problems: list[str] = []
    try:
        text = open(path, encoding="utf-8").read()
    except OSError as exc:
        return [f"не удалось прочитать файл: {exc}"]

    for section in REQUIRED_SECTIONS:
        if section not in text:
            problems.append(f"отсутствует раздел: {section}")

    match = re.search(r"## Аннотация\n\n(.*?)\n\n## ", text, re.S)
    if not match:
        problems.append("не удалось выделить текст аннотации")
    else:
        annotation = re.sub(r"\s+", " ", match.group(1)).strip()
        lowered = annotation.lower()
        if len(annotation) > MAX_ANNOTATION_CHARS:
            problems.append(
                f"аннотация {len(annotation)} знаков, превышает {MAX_ANNOTATION_CHARS}"
            )
        for aspect, keywords in ANNOTATION_KEYWORDS.items():
            if not any(kw in lowered for kw in keywords):
                problems.append(f"в аннотации не упомянут аспект: {aspect}")

    return problems


def main() -> int:
    parser = argparse.ArgumentParser(description="Проверка реферата программы")
    parser.add_argument("--abstract", required=True)
    args = parser.parse_args()

    problems = verify_abstract(args.abstract)
    if problems:
        print("[FAIL] реферат не соответствует требованиям:")
        for problem in problems:
            print(f"  - {problem}")
        return 1

    print("[OK] реферат соответствует требованиям задачи 4.7")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
