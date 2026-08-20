# Скрипты подготовки материалов регистрации (задача 4.7)

Набор скриптов для формирования и проверки пакета документов регистрации
программы для ЭВМ: PDF-распечатка исходного кода, замер объёма исходников,
проверка соответствия формата.

## Установка

```bash
cd scripts/registration_4_7
python3 -m venv --system-site-packages .venv
./.venv/bin/pip install -r requirements.txt
```

Зависимости: `reportlab` (генерация PDF), `PyYAML` (чтение манифестов),
`pypdf` (проверка PDF). Шрифт `DejaVuSansMono` (TTF) используется для кода —
он включён в большинство дистрибутивов Linux.

## Скрипты

| Файл | Назначение |
| --- | --- |
| `common.py` | чтение манифеста, сбор файлов, приоритеты отбора |
| `generate_printout.py` | генерация PDF-распечатки исходного кода |
| `measure_source_size.py` | замер объёма исходников объекта |
| `verify_printout.py` | проверка PDF на соответствие требованиям |
| `verify_abstract.py` | проверка реферата (аннотация ≤ 700 знаков, разделы) |

## Генерация распечатки

```bash
./.venv/bin/python generate_printout.py \
  --manifest ../../docs/registration_4_7/object_a/manifest.yaml \
  --out ../../docs/registration_4_7/object_a/printout/printout.pdf \
  --report ../../docs/registration_4_7/object_a/printout/report.json
```

## Замер объёма

```bash
./.venv/bin/python measure_source_size.py \
  --manifest ../../docs/registration_4_7/object_a/manifest.yaml
```

## Проверка

```bash
./.venv/bin/python verify_printout.py \
  --pdf ../../docs/registration_4_7/object_a/printout/printout.pdf \
  --manifest ../../docs/registration_4_7/object_a/manifest.yaml

./.venv/bin/python verify_abstract.py \
  --abstract ../../docs/registration_4_7/object_a/abstract.md
```

## Стратегии отбора фрагментов

В манифесте (`printout.selection`) задаётся стратегия отбора:

- `strategy: all` — включить все файлы из `source` (объект укладывается в
  лимит страниц);
- `strategy: fragments` — отобрать файлы по списку `priority` (glob-паттерны,
  от наиболее значимых к наименее), пока не исчерпан бюджет `target_max_pages`.
  Оставшиеся файлы попадают в `excluded_files` отчёта.

Это позволяет из одной кодовой базы формировать разные регистрационные объекты
и укладываться в лимит «до 70 страниц» даже когда полный объём кода объекта
превышает лимит.
