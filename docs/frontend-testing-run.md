# Запуск тестов фронтенда (frontend)

См. также: [обзор покрытия](./frontend-testing-overview.md) · [как писать тесты](./frontend-testing-guide.md)

## Требования
- Поднятый Docker Compose стек (`docker compose up -d` из корня репозитория), в частности контейнер `ibs-node`.

## Почему тесты запускаются только через контейнер
`docker-compose.override.yml` запускает в `ibs-node` `npm install && npm run dev` от имени `root`, а контейнер использует тот же bind-mount каталога (`./frontend:/app`), что и хост. Из-за этого часть `node_modules` оказывается во владении `root`, и `npm install`/`npx vitest`, запущенные прямо с хоста, падают с `EACCES`. Всегда выполняйте команды через контейнер:
```bash
docker exec -w /app ibs-node <команда>
```

## Запуск всех тестов
```bash
docker exec -w /app ibs-node npm test
```
(эквивалент `npx vitest run` — так объявлен скрипт `test` в `package.json`; `vitest run` прогоняет тесты один раз и завершает процесс, в отличие от интерактивного watch-режима по умолчанию).

## Watch-режим (для локальной разработки тестов)
```bash
docker exec -it -w /app ibs-node npm run test:watch
```
Обратите внимание на флаг `-it` — watch-режим интерактивен и держит терминал открытым. Для разовых прогонов (перед коммитом, в скриптах) используйте `npm test`.

## Запуск части тестов
Один файл:
```bash
docker exec -w /app ibs-node npx vitest run src/modules/medicalHistory/components/AppointmentAdd/AppointmentAdd.spec.js
```

Целая папка:
```bash
docker exec -w /app ibs-node npx vitest run src/modules/medicalHistory/components
```

По названию теста (`-t`, ищет подстроку в описании `describe`/`it`):
```bash
docker exec -w /app ibs-node npx vitest run -t "calculateDose"
```

## Чтение результата
Vitest печатает дерево `describe`/`it` с ✓/× по каждому файлу и итог `Test Files N passed/failed` + `Tests N passed/failed`. При падении показывается diff ожидаемого/фактического значения и `файл:строка`. Строки `[Vue warn]` в `stderr` сами по себе не являются падением теста — ориентируйтесь на итоговый блок `Failed Tests` и общий статус (`passed`/`failed`) в конце вывода.

## Перед коммитом/PR
Прогоняйте `docker exec -w /app ibs-node npm test` целиком — отдельного CI сейчас нет, это единственная проверка регрессий.

## Возможные проблемы
- **`EACCES` при установке пакетов** — команда выполнена с хоста; используйте `docker exec -w /app ibs-node npm install -D <pkg>`.
- **Тест зависает и не завершается** — компонент/стор делает реальный HTTP-запрос (забыли замокать API-модуль через `vi.mock`), либо метод использует `lodash/debounce`, а тест не использует `vi.useFakeTimers()`.
- **Много `[Vue warn]` в выводе, но тесты зелёные** — обычно не ошибка; чаще всего означает, что Pinia не установлена как плагин (`global: { plugins: [pinia] }`) при использовании Options API хелперов `mapState`/`mapActions`.
