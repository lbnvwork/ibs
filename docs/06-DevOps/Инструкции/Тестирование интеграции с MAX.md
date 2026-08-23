---
code: "INS-004"
title: "Тестирование интеграции с мессенджером MAX"
section: 06-DevOps
date: 2026-08-23
author: ibs-dev-may
---

# Тестирование интеграции с мессенджером MAX

Проект отправляет уведомления пациентам через мессенджер MAX. Интеграция
реализована в классе `Ibs\Context\Communication\Service\MaxChannel`, который
делает запрос `POST {MAX_API_URL}/messages` с заголовком
`Authorization: {MAX_BOT_TOKEN}`.

В этой инструкции описано, как настроить реальные учётные данные MAX и
проверить, что интеграция работает end-to-end.

## 1. Где хранятся секреты

| Файл | Назначение | Попадает в git? |
|---|---|---|
| `.env` (корень проекта) | Реальные `MAX_API_URL`, `MAX_BOT_TOKEN` | ❌ нет (в `.gitignore`) |
| `.env.dist` (корень проекта) | Шаблон с плейсхолдерами для разработчиков | ✅ да |
| `api/.env` | Общие настройки Symfony (БЕЗ секретов MAX) | ✅ да |
| `api/.env.test` | Заглушки `MAX_API_URL=test` / `MAX_BOT_TOKEN=test` для тестов | ✅ да |

**Реальные значения должны лежать ТОЛЬКО в корневом `.env`.** Docker Compose
читает корневой `.env` и передаёт переменные в контейнеры через блок
`environment` (см. `docker-compose.yml` — сервисы `php` и `messenger-worker`).

### Содержимое корневого `.env`

```dotenv
# ...остальные переменные...

MAX_API_URL=https://platform-api2.max.ru
MAX_BOT_TOKEN=<реальный токен бота>
```

> ⚠️ **Важно про `MAX_API_URL`.** Это БАЗОВЫЙ URL API, к которому `MaxChannel`
> дописывает `/messages` (итоговый запрос — `POST {MAX_API_URL}/messages`).
> По документации MAX базовый адрес — **`https://platform-api2.max.ru`**
> (обратите внимание на «2» в имени домена — без неё запросы не пройдут).
> Кроме того, сервер, с которого выполняются запросы, должен доверять
> сертификату Минцифры (см. раздел 2 ниже).

## 2. Применение настроек

После изменения корневого `.env` пересоздайте контейнеры, чтобы переменные
попали в окружение:

```bash
docker compose up -d --build
```

Проверка, что значения доехали до контейнера:

```bash
docker compose exec php sh -c 'echo "URL=$MAX_API_URL"; echo "TOKEN=$MAX_BOT_TOKEN"'
```

### Сертификат Минцифры

Домен `platform-api2.max.ru` работает по сертификату Минцифры (цепочка:
`*.max.ru` → `Russian Trusted Sub CA` → `Russian Trusted Root CA`). Без доверия
к корневому сертификату HTTPS-запросы завершаются ошибкой
`SSL certificate problem: unable to get local issuer certificate`.

Сертификаты уже добавлены в образ при сборке: файлы `docker/php/certs/` и
`docker/php/Dockerfile` (команда `update-ca-certificates`). При
`docker compose up -d --build` они попадают в контейнер автоматически —
добавлять их вручную не нужно.

## 3. Быстрая проверка — консольная команда

Для проверки реальной интеграции добавлена команда
`app:communication:test-max`. Она проходит полный пайплайн
`NotificationService → ChannelRegistry → MaxChannel → MAX API` и пишет результат
в `NotificationLog`.

Синтаксис:

```bash
docker compose exec php php bin/console app:communication:test-max <patient_id> <chat_id> ["текст сообщения"]
```

Пример:

```bash
docker compose exec php php bin/console app:communication:test-max 999999 123456789 "Тестовое сообщение"
```

Что делает команда:

1. Создаёт (если ещё нет) запись `PatientChannelIdentity` для пациента
   `patient_id` с каналом `max` и значением `chat_id`.
2. Отправляет сообщение через `NotificationService` с приоритетом `IMMEDIATE`
   (синхронно — результат виден сразу).
3. Выводит последнюю запись `NotificationLog` (статус, канал, `external_id`,
   адрес, ошибку).

Успешный вывод выглядит примерно так:

```
Отправка тестового MAX-уведомления:
  patient_id: 999999
  chat_id:    123456789
  priority:   immediate
  message:    Тестовое сообщение

Последняя запись NotificationLog:
  status:      sent
  channel:     max
  external_id: max-msg-123
  address:     123456789
```

Код возврата: `0` — успех (`sent`/`delivered`/`read`), `1` — ошибка.

> Команда использует синхронную отправку (`IMMEDIATE`). При временных ошибках
> сети/таймаутах `NotificationService` применяет экспоненциальные повторы
> (1с → 5с → 25с), поэтому при проблемах со связью выполнение может занять до
> ~30 секунд. Критические ошибки (401/400) возвращаются сразу, без повторов.

## 4. Как получить chat_id (адрес получателя)

`chat_id` — это ID диалога/чата/канала получателя в MAX. Бот узнаёт его из
**входящих событий**, а не из настроек. Самый простой способ для теста:

1. Откройте своего бота в MAX (по ссылке вида `max.ru/id..._bot`) и напишите ему
   сообщение со своего аккаунта.
2. Запросите входящие события методом Long Polling:

```bash
curl "https://platform-api2.max.ru/updates?types=message_created" \
  -H "Authorization: <MAX_BOT_TOKEN>"
```

3. В ответе придёт список `updates`, каждый элемент содержит `chat_id`
   (ID диалога) и `user` (отправитель). Этот `chat_id` и есть адрес получателя —
   подставьте его в `app:communication:test-max` или в `value` контакта канала
   `max`.

Подробнее о `GET /updates` и объекте `Update` — в спаршенной документации
(репозиторий `max_parser`): `api/methods/GET/updates.md` и `api/objects/Update.md`.

> Нюанс: `POST /messages` принимает `user_id` (для пользователя) либо `chat_id`
> (для чата/канала). Текущая реализация `MaxChannel` использует `chat_id`.

### Автоматический сбор контактов через диплинк

Вместо ручного получения `chat_id` можно использовать диплинк и команду
`app:communication:collect-max-contacts`:

1. Выдайте пациенту диплинк вида `https://max.ru/<ник_бота>?start=<patientId>`
   (длина `payload` — до 128 символов).
2. Пациент открывает бота — MAX присылает событие `bot_started` с
   `payload = <patientId>` и `chat_id` диалога.
3. Запустите команду (вручную или по расписанию/cron):

```bash
docker compose exec php php bin/console app:communication:collect-max-contacts
```

Команда опрашивает `GET /updates` и сохраняет/обновляет контакт
`PatientChannelIdentity` (`channelType = "max"`, `value = chat_id`) для пациента
`patientId`. Маркер последнего обработанного обновления хранится в файле
`var/max_updates_marker` (чтобы не обрабатывать одни и те же события повторно).

## 5. Проверка через Postman

У API нет публичного HTTP-эндпоинта для запуска отправки уведомления (отправка
инициируется внутренним кодом/очередью), поэтому через Postman проверяются:
авторизация, создание контакта пациента и — опционально — сам API MAX напрямую.

Базовый адрес: `http://localhost:8081`.

### 5.1. Получение JWT-токена

`POST http://localhost:8081/api/login`

Тело (JSON):

```json
{
  "login": "ваш_логин",
  "password": "ваш_пароль"
}
```

В ответе придёт `token` — его нужно подставлять в заголовок
`Authorization: Bearer <token>` для всех защищённых запросов к `/api`.

### 5.2. Создание контакта пациента (канал MAX)

`POST http://localhost:8081/api/patient_channel_identities`

Заголовки:

```
Authorization: Bearer <token>
Content-Type: application/json
```

Тело (JSON):

```json
{
  "patientId": 999999,
  "channelType": "max",
  "value": "123456789"
}
```

- `patientId` — любой целый ID пациента (в сущности это просто поле, без внешнего ключа).
- `channelType` — `"max"` (тип канала).
- `value` — `chat_id` получателя в MAX.

Проверить список контактов пациента:

`GET http://localhost:8081/api/patient_channel_identities?patientId=999999`

### 5.3. Проверка самого API MAX напрямую (опционально)

Чтобы отдельно убедиться, что токен и URL корректны, можно вызвать API MAX
напрямую (обратите внимание: `chat_id` передаётся в query-строке):

`POST https://platform-api2.max.ru/messages?chat_id=123456789`

Заголовки:

```
Authorization: <MAX_BOT_TOKEN>
Content-Type: application/json
```

Тело (JSON):

```json
{
  "text": "Тестовое сообщение"
}
```

> Наш `MaxChannel` отправляет `chat_id` в query-строке URL, а текст — в
> JSON-теле `{"text": "..."}` (см. исходник `MaxChannel`). Это соответствует
> документации MAX.

## 6. Проверка результата в БД

Каждая попытка отправки фиксируется в таблице `notification_logs`. Проверить
последнюю запись можно SQL-запросом:

```bash
docker compose exec db psql -U symfony -d symfony -c \
  "SELECT id, patient_id, channel_type, status, external_id, recipient_address, error_message, created_at
   FROM notification_logs
   ORDER BY id DESC LIMIT 5;"
```

- `status = sent` — сообщение успешно принято MAX.
- `status = failed` — смотрите `error_message`.
- `external_id` — идентификатор сообщения, который вернул MAX.

## 7. Возможные проблемы и их причины

| Симптом | Вероятная причина |
|---|---|
| `Channel "max" is not available.` | Пустые `MAX_API_URL`/`MAX_BOT_TOKEN` — переменные не доехали до контейнера. Проверьте шаг 2. |
| HTTP 401 / «Not authorized» | Неверный `MAX_BOT_TOKEN`. |
| HTTP 404 | Неверный `MAX_API_URL`: должен быть `https://platform-api2.max.ru` (с «2»). |
| Ошибка SSL/TLS (certificate problem) | Сертификат Минцифры не добавлен в образ — проверьте `docker/php/certs/` и `docker/php/Dockerfile` (см. шаг 2). |
| Таймаут / retryable ошибка | Проблема сети; команда может выполнять повторы до ~30 сек. |
| `Address not configured for channel "max".` | Для пациента нет контакта `max` (в команде он создаётся автоматически; при вызове из кода — проверьте `patient_channel_identities`). |

