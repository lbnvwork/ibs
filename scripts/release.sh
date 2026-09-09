#!/usr/bin/env bash
# Версионирование приложения (задача 3.83): SemVer MAJOR.MINOR.PATCH = итерация.спринт.деплой.
# Источник истины — git-тег v<MAJOR>.<MINOR>.<PATCH>; PATCH инкрементится автоматически
# от последнего тега спринта. Переходы MAJOR/MINOR — вручную (аргументы).
#
# Использование:
#   scripts/release.sh 0 3 "спринт 3, задачи 3.78/3.80/3.81"
set -euo pipefail

# Текущая версия из последнего тега (или v0.0.0, если тегов нет).
current() {
    git describe --tags --abbrev=0 2>/dev/null || echo "v0.0.0"
}

CUR="$(current)"
CUR_MAJOR="$(echo "${CUR#v}" | cut -d. -f1)"
CUR_MINOR="$(echo "${CUR#v}" | cut -d. -f2)"

MAJOR="${1:-$CUR_MAJOR}"
MINOR="${2:-$CUR_MINOR}"
MESSAGE="${3:-release v${MAJOR}.${MINOR}}"

# Последний PATCH в спринте MAJOR.MINOR.
LAST="$(git tag -l "v${MAJOR}.${MINOR}.*" | sort -V | tail -1)"
if [[ -z "$LAST" ]]; then
    PATCH=1
else
    PATCH=$(( "${LAST##*.}" + 1 ))
fi

VERSION="v${MAJOR}.${MINOR}.${PATCH}"

echo "Текущая версия: ${CUR}"
echo "Новая версия:   ${VERSION}"

if git rev-parse "${VERSION}" >/dev/null 2>&1; then
    echo "Тег ${VERSION} уже существует."
    exit 1
fi

git tag -a "${VERSION}" -m "${MESSAGE}"
echo "Создан тег ${VERSION}"
