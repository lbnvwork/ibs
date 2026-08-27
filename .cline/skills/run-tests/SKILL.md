---
name: run-tests
description: Запуск тестов проекта (бэкенд и фронтенд). Использовать перед коммитом или PR.
---

# Run-tests

- Бэкенд: `docker compose exec php vendor/bin/phpunit` + `docker compose exec php vendor/bin/phpstan analyse --no-progress`.
- Фронтенд: `docker compose exec node npm run test:unit`.
- Тесты/phpstan зелёные — обязательное условие перед PR. Если падают — чини. Результат прогона (`phpunit N/N` · `phpstan OK` · `npm run test:unit N/N`) запиши в отчёт (СДЕЛАНО) и в PR (секция «Тесты»).
