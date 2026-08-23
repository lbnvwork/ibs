---
code: "INS-003"
title: "Настройка и эксплуатация канала MAX"
section: 06-DevOps
date: 2026-08-23
author: ibs-dev-may
---

# Настройка и эксплуатация канала MAX

## Назначение
Как настроить и эксплуатировать канал уведомлений мессенджера MAX (задача 3.1): конфигурация `.env`, отправка уведомлений пациентам, привязка `chat_id` через диплинк, приём входящих событий (Long Polling / Webhook). Полезна DevOps при деплое и разработчику при отладке.

## Предусловия
- Docker + Docker Compose; стек поднят: `docker compose up -d --build` (образ `php` уже содержит сертификаты Минцифры из `docker/php/certs/` — они нужны для HTTPS к MAX).
- Доступы MAX: базовый URL API, токен бота, ник бота, секрет webhook.

## Шаги

### 1. Конфигурация `.env`
В корневом `.env` (в `.gitignore`, сюда кладутся реальные секреты) задать:

```dotenv
MAX_API_URL=https://platform-api2.max.ru   # базовый URL, к нему MaxChannel дописывает /messages
MAX_BOT_TOKEN=<токен бота>
MAX_WEBHOOK_SECRET=<секрет webhook>        # только для Webhook (на деплое)
MAX_BOT_NICKNAME=id463246156997_bot        # ник бота из публичной ссылки max.ru/<ник>
```

После изменения — пересоздать контейнеры: `docker compose up -d`.
Проверка: `docker compose exec php sh -c 'echo "$MAX_API_URL $MAX_BOT_TOKEN"'`.

### 2. Отправка уведомления (ручная проверка)
```bash
docker compose exec php php bin/console app:communication:test-max <patient_id> <chat_id> ["текст"]
```
Команда создаёт контакт `max` для пациента (если его нет), шлёт сообщение через `NotificationService → MaxChannel` и печатает последнюю запись `notification_logs`.

### 3. Привязка chat_id пациента (диплинк)
1. Врач (JWT) запрашивает диплинк: `GET /api/patients/{id}/max-deeplink` → `{"url":"https://max.ru/<ник>?start=<token>"}`.
2. Пациент открывает бота → MAX шлёт событие `bot_started` (`payload=<token>`, `chat_id`).
3. Система резолвит токен в `patientId` и сохраняет/обновляет контакт `max` в `patient_channel_identities` (`value=chat_id`).

### 4. Входящий канал: Long Polling (dev)
Контейнер `max-updates-listener` поднят через `docker compose up -d` и опрашивает `GET /updates` в цикле.
- Логи: `docker compose logs -f max-updates-listener`
- Одноразовый запуск: `docker compose exec php php bin/console app:communication:collect-max-contacts`

### 5. Входящий канал: Webhook (production)
1. Зарегистрировать подписку (на деплое, нужен публичный HTTPS-URL): `docker compose exec php php bin/console app:communication:max-webhook-subscribe https://<домен>/api/max/webhook`
2. Эндпоинт `POST /api/max/webhook` проверяет заголовок `X-Max-Bot-Api-Secret` (должен совпадать с `MAX_WEBHOOK_SECRET`); неверный → 403.
3. После активации Webhook Long Polling не работает (MAX разрешает один механизм) — контейнер `max-updates-listener` можно отключить.

## Примеры
```bash
# Проверка реальной отправки
docker compose exec php php bin/console app:communication:test-max 999011 42342534 "Тестовое уведомление"

# Проверить лог отправки в БД
docker compose exec db psql -U symfony -d symfony -c \
  "SELECT id, patient_id, channel_type, status, recipient_address, external_id FROM notification_logs ORDER BY id DESC LIMIT 5;"
```

## Чек-лист
- [ ] `MAX_API_URL` / `MAX_BOT_TOKEN` заданы в корневом `.env`; реальные токены не в git (в git — только заглушки `.env.dist`)
- [ ] `app:communication:test-max` вернул успех (`notification_logs.status='sent'`)
- [ ] Диплинк выдаётся, `bot_started` сохраняет `chat_id`
- [ ] Webhook (на деплое): неверный секрет → 403, верный → 200

## Примечания
- `MAX_ENV` из раннего плана в реализации не используется — фактически работают `MAX_WEBHOOK_SECRET` (webhook) и `MAX_BOT_NICKNAME` (диплинк).
- MAX не возвращает id сообщения в ответе на `POST /messages` → `external_id=null`, успех трактуется как `sent`.
- HTTPS к MAX — по цепочке сертификатов Минцифры (`Russian Trusted Root CA` → `Russian Trusted Sub CA`), они уже добавлены в образ при сборке.
- Подробное руководство по тестированию: `docs/06-DevOps/Инструкции/Тестирование интеграции с MAX.md` (INS-004).
