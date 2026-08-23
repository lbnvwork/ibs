---
name: make-postman
description: Подготовка Postman-коллекции для ручного тестирования API и прогон через Postman CLI (или curl).
---

# Make-postman

- Собери запросы по сценариям задачи в коллекцию (Postman Collection v2.1). Источник эндпоинтов — OpenAPI-спека API Platform `/api/docs.json`.
- Сохрани JSON в `api/postman/` (или приложи к статье задачи).
- В коллекции заложи получение JWT (эндпоинт login) и подстановку в `Authorization: Bearer <token>` — иначе запросы будут 401.
- Прогон с хоста: `postman collection run <файл>.json --env-var baseUrl=http://localhost:8005`.
- Фолбэк без Postman CLI — эквивалентные curl-команды (приложи рядом с коллекцией).
- Результат прогона (passed/failed) — в статью задачи (секция «Ручное тестирование»); найденные баги — в `docs/05-Тестирование/Баги/`.
