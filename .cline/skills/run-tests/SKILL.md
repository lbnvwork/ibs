---
name: run-tests
description: Запуск тестов проекта (бэкенд и фронтенд). Использовать перед коммитом или PR.
---

# Run-tests

- Бэкенд: `docker compose exec php vendor/bin/phpunit`
- Фронтенд: `docker compose exec node npm run test:unit`
- Тесты зелёные — обязательное условие перед PR. Если падают — чини.
