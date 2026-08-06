# Запуск тестов бэкенда (api)

См. также: [обзор покрытия](./backend-testing-overview.md) · [как писать тесты](./backend-testing-guide.md)

## Требования
- Поднятый Docker Compose стек проекта (`docker compose up -d` из корня репозитория), в частности контейнеры `ibs-php` и `ibs-db`.
- Существующая тестовая база данных `symfony_test` (см. «Разовая настройка» ниже).

## Почему тесты запускаются только в контейнере
Хостовый PHP (8.3.6) не имеет расширения `pdo_pgsql`, поэтому Doctrine/PHPUnit не могут подключиться к PostgreSQL при запуске с хоста напрямую. Каталог `api/` примонтирован в контейнер `ibs-php` (`./api:/var/www/html`), поэтому `vendor/`, установленный один раз, виден и на хосте, и в контейнере — просто выполняйте команды внутри контейнера.

## Разовая настройка тестовой БД
Тесты работают с **отдельной** базой `symfony_test`, а не с `symfony` (это дев-база с реальными данными — тесты никогда не должны её трогать). Если `symfony_test` ещё не создана:

```bash
docker exec ibs-db psql -U symfony -d postgres -c "CREATE DATABASE symfony_test OWNER symfony"
docker exec ibs-php sh -c 'cd /var/www/html && php bin/console doctrine:migrations:migrate --env=test --no-interaction'
```

Это нужно сделать один раз (и повторить, если контейнер с БД пересоздавался с нуля).

## Запуск всех тестов
```bash
docker exec ibs-php sh -c 'cd /var/www/html && vendor/bin/phpunit'
```
`phpunit.dist.xml` уже форсирует `APP_ENV=test`, поэтому ничего дополнительно указывать не нужно — конфигурация сама переключит подключение на `symfony_test` (см. `config/packages/doctrine.yaml` → `when@test: dbal.dbname_suffix: '_test'`).

## Запуск части тестов
Один файл:
```bash
docker exec ibs-php sh -c 'cd /var/www/html && vendor/bin/phpunit tests/Ibs/Context/AICDSS/AiDosage/DosageRecommendationEngineTest.php'
```

Целая директория (например, весь bounded context):
```bash
docker exec ibs-php sh -c 'cd /var/www/html && vendor/bin/phpunit tests/Ibs/Context/TreatmentTherapy'
```

По имени теста/маске (`--filter`, поддерживает регулярные выражения):
```bash
docker exec ibs-php sh -c 'cd /var/www/html && vendor/bin/phpunit --filter testLastnameSearchIsCaseInsensitivePartialMatch'
docker exec ibs-php sh -c 'cd /var/www/html && vendor/bin/phpunit --filter Dosage'
```

## Чтение результата
- `.` — тест прошёл, `F` — упавший assert, `E` — необработанное исключение, `S` — skipped.
- При падении PHPUnit печатает имя теста, ожидаемое/фактическое значение и `файл:строка`.
- В `phpunit.dist.xml` включены `failOnDeprecation`/`failOnNotice`/`failOnWarning` — тест упадёт даже на PHP-деprecation, это сделано намеренно, чтобы не копить технический долг.
- Полезные флаги: `--testdox` (человекочитаемый список сценариев вместо точек), `-v`/`--debug` при разборе зависаний.

## Перед коммитом/PR
Прогоняйте полный набор (`vendor/bin/phpunit` без фильтров) — отдельного CI-пайплайна в проекте нет, это единственная проверка регрессий перед отправкой изменений.

## Частые проблемы
- **`Could not find service "test.service_container"`** — отсутствует/повреждена конфигурация тестового окружения; проверьте, что `config/packages/test/framework.yaml` существует.
- **Тесты, похоже, читают/пишут в `symfony`, а не в `symfony_test`** — почти всегда значит, что `APP_ENV` не форсируется в `test` (см. `<env name="APP_ENV" value="test" force="true"/>` в `phpunit.dist.xml`, оно нужно из-за реального `APP_ENV=dev`, который Docker прокидывает в контейнер как OS env var) — не убирайте эту настройку.
- **Ошибка подключения к БД** — убедитесь, что `ibs-db` поднят и `symfony_test` создана (см. «Разовая настройка» выше).
