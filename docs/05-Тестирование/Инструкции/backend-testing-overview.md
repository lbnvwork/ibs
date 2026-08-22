# Обзор тестового покрытия — api (бэкенд)

См. также: [запуск тестов](./backend-testing-run.md) · [как писать тесты](./backend-testing-guide.md)

Состояние на 2026-08-06: **68 тестов** в **31 файле**, весь набор зелёный (`vendor/bin/phpunit`).

## Покрытие по bounded context'ам

| Контекст | Тестовые файлы (`tests/Ibs/Context/...`) | Что проверяется |
|---|---|---|
| **PatientManagement** | `Entity/PatientPersistenceTest.php`<br>`PatientSearchApiTest.php` | Персистентность `Patient`/`PatientPhone`/`Hospital` через Doctrine; поиск пациентов через `/api/patients` (частичное совпадение по фамилии без учёта регистра, фильтр по больнице) |
| **TreatmentTherapy** | `Entity/TreatmentPersistenceTest.php`<br>`Filter/ActiveTreatmentFilterApiTest.php`<br>`Filter/PatientFiltersApiTest.php`<br>`Repository/Mkb10RepositoryTest.php`<br>`Repository/TestHistoryRepositoryTest.php`<br>`Repository/TreatmentRepositoryTest.php`<br>`Resource/PatientStatusApiTest.php`<br>`State/AppointmentSaveProcessorApiTest.php`<br>`State/Mkb10ProvidersApiTest.php`<br>`State/TestHistoryLatestProviderApiTest.php` | Персистентность лечения; репозиторные запросы (активные пациенты по лечению, история анализов, МКБ-10); кастомные API Platform фильтры/провайдеры/процессоры (сохранение назначений, статус пациента, последний анализ) |
| **AICDSS** | `AiDosage/DosageRecommendationApiTest.php`<br>`AiDosage/DosageRecommendationEngineTest.php`<br>`Command/SeedGeneticMarkersCommandTest.php`<br>`Entity/PatientGeneticResultApiTest.php`<br>`Pharmacogenetics/PharmacogeneticsApiTest.php`<br>`Pharmacogenetics/PharmacogeneticsServiceTest.php` | Движок расчёта дозы варфарина (граничные случаи: неизвестное лечение, нет данных МНО, МНО > 5.0), HTTP-эндпоинт рекомендаций дозы, фармакогенетика (генетические маркеры и их влияние на дозу), команда сидирования справочника маркеров |
| **LabIoTGateway** | `Entity/PatientVitalsApiTest.php`<br>`State/PatientVitalsLatestBatchProviderApiTest.php` | Витальные показатели пациента через API, батч-провайдер «последних» показателей |
| **AdaptivePlanning** | `Entity/AdaptivePlanningPersistenceTest.php` | Персистентность сущностей планирования (`TestPlan`/`HospitalTestPlan`/`Holiday`/`Supervisor`) |
| **Communication** | `Entity/CommunicationPersistenceTest.php` | Персистентность SMS-сущностей (`SmsIn`/`SmsOut`/`SmsOutStatus`/`SmsOutPacket`/`SmsTemplate`) |
| **SecurityIdentity** | `Command/HashPasswordsCommandTest.php`<br>`Entity/SecurityIdentityPersistenceTest.php`<br>`EventListener/JwtCreatedListenerTest.php`<br>`Security/LoginFlowApiTest.php` | Хэширование паролей, персистентность `User`/`MedicalPersonnel`, добавление кастомных данных в JWT при создании токена, полный сценарий логина (`POST /api/login`) |
| **Ibs\Shared** | `Common/Entity/MetadataApiTest.php`<br>`Http/EventListener/TrimAndNullifyStringsListenerTest.php`<br>`OpenApi/JwtDecoratorTest.php` | Общий ресурс метаданных; листенер, который триммит строки и превращает пустые строки в `null` во входящих запросах; декоратор OpenAPI-схемы для JWT-аутентификации |
| **Smoke** | `ApplicationAvailabilityTest.php`<br>`DatabaseConnectionTest.php` | Базовая проверка, что приложение поднимается и БД доступна — «канарейка» для сломанной конфигурации |

Также есть `tests/Support/AuthenticatesUsers.php` — не тест, а общий трейт для функциональных тестов (создание пользователя + получение JWT), см. [гайд по написанию тестов](./backend-testing-guide.md).

## Не покрыто тестами (известные пробелы)
- Контексты-заглушки без реализации (`AnalyticsReporting`, `PhysicianDashboard`, `PatientPortal`, `DispensaryControl`, `SystemAPI`) — там нет кода, поэтому и тестов нет.
- Нет отдельных unit-тестов на «немые» DTO/Value Object без поведения — они проверяются косвенно через сценарии `*ApiTest`/`*PersistenceTest`.
- Нет нагрузочного/производительного тестирования.

## Как поддерживать этот обзор
При добавлении нового тестового файла — допишите строку в таблицу выше (или заведите новую строку контекста). Актуальные цифры можно получить командой:
```bash
docker exec ibs-php sh -c 'cd /var/www/html && vendor/bin/phpunit --testdox' | tail -5
```
