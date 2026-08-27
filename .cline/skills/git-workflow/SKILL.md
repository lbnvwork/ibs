---
name: git-workflow
description: Работа с git — ветки, коммиты, пул-реквесты. Использовать при создании ветки, коммите или PR.
---

# Git-workflow

- Ветка на задачу от `develop`: `S_NN_название`.
- Коммит: `<код> <тип> <описание>` (напр. `3.19 doc new ...`).
- По завершении создай PR: `gh pr create --base develop --head <ветка> --title 'S.NN Название' --body-file <файл>`. Тело — по шаблону `docs/03-Задачи/_Шаблоны/PR.md`.
- Перед PR: прогон тестов + `git merge develop` в своей ветке.
- Не пушить напрямую в `develop`/`main`.

## Порядок при завершении задачи (обязательно)

1. **Код:**
   ```bash
   git fetch origin
   git pull origin develop                        # или: checkout develop → pull → checkout <ветка> → merge develop
   git checkout -b S_NN_название                  # если ветки нет — от актуального develop
   docker compose exec php vendor/bin/phpunit                          # бэкенд — обязательно
   docker compose exec php vendor/bin/phpstan analyse --no-progress    # бэкенд — обязательно
   docker compose exec node npm run test:unit                          # фронтенд — обязательно
   git push -u origin <ветка>                     # по отмашке
   ```
2. **Docs (`ibs-docs`):**
   ```bash
   git pull origin main
   # <правки> → git diff
   ```
3. **Отчёт:** `СДЕЛАНО/ФАЙЛЫ/БЛОКЕРЫ/КОММИТ (название)` → руководитель.

> Синхронизация — ответственность агента ДО отчёта. Не передавай коммит на устаревшей базе.
