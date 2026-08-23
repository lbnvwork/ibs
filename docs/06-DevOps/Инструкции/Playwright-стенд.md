---
code: "INS-002"
title: "Playwright-стенд для E2E"
section: 06-DevOps
date: 2026-08-23
author: ibs-devops-jul
---

# Playwright-стенд для E2E

## Назначение
Запуск E2E-тестов Playwright в Docker против поднятого приложения (nginx). Сервис `e2e` — общий, описан в `docker-compose.yml` (в git); его использует тестировщик (Марта, `ibs-tester-mar`) — у каждого агента образ собирается под своим `${COMPOSE_PROJECT_NAME}-e2e`.

## Предусловия
- Docker + Docker Compose; `.env` с `COMPOSE_PROJECT_NAME`.
- Внешняя сеть `app-network` создана (`docker network ls`).
- Стек приложения поднят: `docker compose up -d --build` (php, db, node, nginx).

## Шаги
1. Подними стек: `docker compose up -d --build`.
2. Запусти тесты: `docker compose run --rm e2e npx playwright test`.
3. Результат — в консоли. Артефакты (trace/скриншоты) — в `tests/e2e/test-results/` на хосте.

## Просмотр trace
1. Найди `trace.zip` в `tests/e2e/test-results/` (путь вида `test-results/<файл-теста>/<название-теста>/trace.zip`).
2. `docker compose run --rm -p 9323:9323 e2e npx playwright show-trace --host 0.0.0.0 test-results/<...>/trace.zip`
3. Открой `http://localhost:9323`.

## Просмотр HTML-отчёта
`docker compose run --rm -p 9323:9323 e2e npx playwright show-report --host 0.0.0.0` → `http://localhost:9323`.

## Примеры
- Полный прогон: `docker compose run --rm e2e npx playwright test`
- Один файл: `docker compose run --rm e2e npx playwright test example.spec.ts`

## Чек-лист
- [ ] Стек поднят (`docker compose ps` — php/nginx в работе)
- [ ] `docker compose run --rm e2e npx playwright test` — зелёный
- [ ] `tests/e2e/test-results/` содержит `trace.zip` и скриншоты

## Примечания
- Версия `@playwright/test` (`tests/e2e/package.json`) обязана совпадать с версией браузеров в `tests/e2e/Dockerfile` (`FROM mcr.microsoft.com/playwright:v1.62.0-noble`).
- После смены зависимостей пересобери образ: `docker compose build e2e`; для сброса закешированного volume — `docker compose down -v` (volume `e2e_node_modules`).
