"""Замер объёма исходных файлов регистрационного объекта в машиночитаемой форме.

Считает суммарный размер файлов, перечисленных в манифесте (без зависимостей и
артефактов — только исходники, подпадающие под ``include_extensions`` и не
исключённые ``exclude_patterns``). Значение используется в реферате.

Использование:
    python measure_source_size.py --manifest docs/registration_4_7/object_a/manifest.yaml
"""
from __future__ import annotations

import argparse
import json
import os
import sys

import common


def measure(manifest: dict) -> dict:
    files = common.collect_files(manifest, common.repo_root())
    total_bytes = 0
    for file_path, _rel in files:
        try:
            total_bytes += os.path.getsize(file_path)
        except OSError as exc:
            print(f"[warn] {file_path}: {exc}", file=sys.stderr)
    return {
        "object_id": manifest.get("object", {}).get("id"),
        "working_title": manifest.get("object", {}).get("working_title"),
        "files": len(files),
        "bytes": total_bytes,
        "human": common.format_size(total_bytes),
    }


def main() -> int:
    parser = argparse.ArgumentParser(description="Замер объёма исходников объекта")
    parser.add_argument("--manifest", required=True)
    parser.add_argument("--json", action="store_true", help="вывести в формате JSON")
    args = parser.parse_args()

    manifest = common.load_manifest(args.manifest)
    result = measure(manifest)

    if args.json:
        print(json.dumps(result, ensure_ascii=False, indent=2))
    else:
        print(f"Объект: {result['object_id']} — {result['working_title']}")
        print(f"Файлов: {result['files']}")
        print(f"Объём исходников: {result['bytes']} байт ({result['human']})")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
