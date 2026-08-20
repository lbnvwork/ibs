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
| `.env` (корень проекта) | Реальные `MAX_API_URL`, `MAX_BOT_TOKEN`, `MAX_WEBHOOK_SECRET` | ❌ нет (в `.gitignore`) |
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
MAX_WEBHOOK_SECRET=<секрет вебхука — задаётся на деплое>
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
docker compose exec php php bin/console app:communication:test-max 999011 42342534 "Тестовое уведомление"
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
  patient_id: 999011
  chat_id:    42342534
  priority:   immediate
  message:    Тестовое уведомление

Последняя запись NotificationLog:
  status:      sent
  channel:     max
  external_id: -
  address:     42342534
```

Код возврата: `0` — успех (`sent`/`delivered`/`read`), `1` — ошибка.

> Команда использует синхронную отправку (`IMMEDIATE`). При временных ошибках
> сети/таймаутах `NotificationService` применяет экспоненциальные повторы
> (1с → 5с → 25с), поэтому при проблемах со связью выполнение может занять до
> ~30 секунд. Критические ошибки (401/400) возвращаются сразу, без повторов.

## 4. Как получить chat_id (адрес получателя)

Тестовый бот: **bloodcontrol** — ник `id463246156997_bot`, ссылка
`https://max.ru/id463246156997_bot`, user_id `399549989`. В тестах уже получен
`chat_id = 42342534` (диалог с пользователем «Максим»).

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

Вместо ручного получения `chat_id` можно выдавать пациенту диплинк — контакт
сохранится автоматически. Примеры для тестового бота:

```text
https://max.ru/id463246156997_bot?start=999011    # пациент 999011
https://max.ru/id463246156997_bot?start=<patientId>
```

1. Пациент открывает бота по диплинку — MAX присылает событие `bot_started`
   с `payload = <patientId>` и `chat_id` диалога.
2. Команда `app:communication:collect-max-contacts` опрашивает `GET /updates`
   и сохраняет/обновляет контакт `PatientChannelIdentity`
   (`channelType = "max"`, `value = chat_id`) для пациента `patientId`.
3. Маркер последнего обработанного обновления хранится в файле
   `var/max_updates_marker` (чтобы не обрабатывать события повторно).

#### Контейнер-слушатель (Long Polling)

Для непрерывного опроса в `docker-compose.yml` поднят сервис
`max-updates-listener` (аналог `messenger-worker`). Он запускает команду в
режиме `--loop` и сам держит Long Polling:

```yaml
command: sh -c "php bin/console app:communication:collect-max-contacts --loop --timeout=25 --env=prod"
```

Проверить его работу:

```bash
docker compose logs -f max-updates-listener
```

Одноразовый запуск для отладки:

```bash
docker compose exec php php bin/console app:communication:collect-max-contacts
```

#### Webhook (для production)

MAX рекомендует в production использовать Webhook вместо Long Polling. Всё
подготовлено, но **Webhook не активируется до деплоя** (нужен публичный
HTTPS-URL на порту 443 с доверенным сертификатом):

- эндпоинт `POST /api/max/webhook` (проверяет заголовок `X-Max-Bot-Api-Secret`);
- секрет `MAX_WEBHOOK_SECRET` (в `.env`, `.env.dist`, `docker-compose`);
- на деплое регистрируем подписку командой:

```bash
docker compose exec php php bin/console app:communication:max-webhook-subscribe https://<домен>/api/max/webhook
```

После активации Webhook Long Polling не работает (MAX разрешает только один
механизм) — контейнер-слушатель можно отключить.

## 5. Проверка результата в БД

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

## 6. Возможные проблемы и их причины

| Симптом | Вероятная причина |
|---|---|
| `Channel "max" is not available.` | Пустые `MAX_API_URL`/`MAX_BOT_TOKEN` — переменные не доехали до контейнера. Проверьте шаг 2. |
| HTTP 401 / «Not authorized» | Неверный `MAX_BOT_TOKEN`. |
| HTTP 404 | Неверный `MAX_API_URL`: должен быть `https://platform-api2.max.ru` (с «2»). |
| Ошибка SSL/TLS (certificate problem) | Сертификат Минцифры не добавлен в образ — проверьте `docker/php/certs/` и `docker/php/Dockerfile` (см. шаг 2). |
| Таймаут / retryable ошибка | Проблема сети; команда может выполнять повторы до ~30 сек. |
| `Address not configured for channel "max".` | Для пациента нет контакта `max` (в команде он создаётся автоматически; при вызове из кода — проверьте `patient_channel_identities`). |

