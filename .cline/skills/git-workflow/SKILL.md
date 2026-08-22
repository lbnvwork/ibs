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
