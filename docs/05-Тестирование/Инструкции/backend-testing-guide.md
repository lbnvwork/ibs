# Как писать тесты в api (Symfony / API Platform)

См. также: [запуск тестов](./backend-testing-run.md) · [обзор покрытия](./backend-testing-overview.md)

Тесты лежат в `api/tests/` и зеркалируют структуру bounded context'ов из `api/src/Ibs/Context/{Context}/...` — тест для `src/Ibs/Context/TreatmentTherapy/Repository/TreatmentRepository.php` находится в `tests/Ibs/Context/TreatmentTherapy/Repository/TreatmentRepositoryTest.php`. Пространство имён тестов всегда `App\Tests\...` (см. `composer.json` → `autoload-dev`).

## Три вида тестов — что когда выбирать

| Базовый класс | Когда использовать | Поднимает контейнер/БД? |
|---|---|---|
| `PHPUnit\Framework\TestCase` | Чистая логика без зависимостей от Symfony/Doctrine | Нет |
| `Symfony\Bundle\FrameworkBundle\Test\KernelTestCase` | Сервисы, репозитории, персистентность сущностей — всё, что нужно достать из DI-контейнера и/или Doctrine `EntityManager`, но без HTTP | Да, через `self::bootKernel()` |
| `Symfony\Bundle\FrameworkBundle\Test\WebTestCase` | Полный HTTP-сценарий через маршруты API Platform (`/api/...`) — самый частый вид теста в проекте | Да, через `static::createClient()` |

## Обязательный паттерн отката транзакции

В проекте нет общего DAMA/doctrine-test-bundle — каждый тест, трогающий БД, сам изолирует свои данные, открывая транзакцию в `setUp()` и откатывая её в `tearDown()`:

```php
protected function setUp(): void
{
    self::bootKernel(); // для WebTestCase вместо этого — static::createClient()

    $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    $this->entityManager->getConnection()->beginTransaction();
}

protected function tearDown(): void
{
    $connection = $this->entityManager->getConnection();
    if ($connection->isTransactionActive()) {
        $connection->rollBack();
    }

    $this->entityManager->close();

    parent::tearDown();
}
```
Без этого тесты будут накапливать данные в `symfony_test` и мешать друг другу при повторных запусках.

## Пример: тест репозитория (`KernelTestCase`)
Из `tests/Ibs/Context/TreatmentTherapy/Repository/TreatmentRepositoryTest.php`:
```php
class TreatmentRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private TreatmentRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->getConnection()->beginTransaction();
        $this->repository = static::getContainer()->get(TreatmentRepository::class);
    }

    protected function tearDown(): void { /* см. паттерн отката выше */ }

    public function testGetActivePatientIdsReturnsOnlyPatientsWithOpenTreatment(): void
    {
        $drug = $this->createDrug();
        $activePatient = $this->createPatient('Активный');
        $this->createTreatment($activePatient, $drug, realEndDt: null);

        $finishedPatient = $this->createPatient('Завершённый');
        $this->createTreatment($finishedPatient, $drug, realEndDt: new \DateTime('-1 day'));

        $this->entityManager->flush();

        $result = $this->repository->getActivePatientIds([$activePatient->getId(), $finishedPatient->getId()]);

        $this->assertContains($activePatient->getId(), $result);
        $this->assertNotContains($finishedPatient->getId(), $result);
    }

    // приватные createPatient()/createDrug()/createTreatment() — фабрики сущностей для конкретного теста
}
```

## Пример: функциональный HTTP-тест с аутентификацией (`WebTestCase`)
Почти все `/api/*` маршруты защищены JWT (`IS_AUTHENTICATED_FULLY`). Для этого есть готовый трейт `App\Tests\Support\AuthenticatesUsers` — не пишите логин-боилерплейт заново:
```php
class PatientSearchApiTest extends WebTestCase
{
    use AuthenticatesUsers;

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->getConnection()->beginTransaction();
    }

    protected function tearDown(): void { /* см. паттерн отката выше */ }

    public function testLastnameSearchIsCaseInsensitivePartialMatch(): void
    {
        $token = $this->createAuthenticatedClient($this->client, $this->entityManager);

        $matching = $this->createPatient('Иванов');
        $this->entityManager->flush();

        $this->client->request(
            'GET',
            '/api/patients?lastname=иван',
            server: array_merge($this->authHeader($token), ['HTTP_ACCEPT' => 'application/json'])
        );

        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $ids = array_column(json_decode((string) $response->getContent(), true), 'id');
        $this->assertContains($matching->getId(), $ids);
    }
}
```
`AuthenticatesUsers` даёт `createUser()`, `obtainToken()`, `createAuthenticatedClient()` (создаёт пользователя и сразу возвращает JWT) и `authHeader($token)` (готовый заголовок `Authorization: Bearer ...`).

## Пример: тест бизнес-логики без HTTP (сервис из контейнера)
Из `tests/Ibs/Context/AICDSS/AiDosage/DosageRecommendationEngineTest.php`:
```php
public function testUnknownTreatmentReturnsNotFoundExplanation(): void
{
    $result = $this->engine->recommend(999999);

    $this->assertSame([], $result['variants']);
    $this->assertSame('Лечение не найдено', $result['explanation']);
}
```
Даже когда логика «чистая», если сервис зарегистрирован в контейнере и зависит от Doctrine (как `DosageRecommendationEngine`), проще достать его через `KernelTestCase::getContainer()`, чем руками собирать все зависимости.

## Соглашения
- **Имя класса**: `{ИмяКласса}Test.php` для юнит-/репозиторных тестов, `{Сценарий}ApiTest.php` для HTTP-функциональных тестов.
- **Имя метода**: `testПолноеОписаниеПоведения(): void` — например, `testMnoAboveFiveReturnsSpecialWarningWithNoVariants`, а не `testCase1`. Название должно быть понятно без чтения тела теста.
- **Assertions**: предпочитайте `assertSame` вместо `assertEquals` (строгое сравнение типа и значения), кроме случаев сравнения объектов с разной идентичностью.
- **Тестовые данные**: приватные фабричные методы (`createPatient()`, `createDrug()`, `createTreatment()`...) прямо внутри тестового класса. Общий fixture-слой в проекте намеренно не заведён — единственное исключение — трейт `AuthenticatesUsers`, оправданное тем, что аутентификация нужна почти всем функциональным тестам.
- Никогда не запускайте тесты без форсированного `APP_ENV=test` напрямую против дев-базы `symfony` — только против `symfony_test`.

## Важный нюанс: добавление нового `#[ApiResource]`
Если добавляете `#[ApiResource]`-класс и одновременно правите `config/services.yaml`, исключая что-то из автовайринга (`exclude: [...]`) — никогда не исключайте директорию, где лежит такой класс: API Platform тихо (без ошибки, просто 404) уберёт его маршрут. Перед исключением директории проверьте:
```bash
grep -rl '#\[ApiResource' src/Ibs/Context/{Контекст}
```
Это уже становилось причиной реального бага (404 на `/api/dosage/recommendation`), который поймали именно тесты — стоит писать тест на новый эндпоинт сразу, а не постфактум.

## Куда класть новый тест
`tests/Ibs/Context/{Контекст}/{Подпапка}/{Имя}Test.php`, где `{Контекст}` — один из `PatientManagement`, `TreatmentTherapy`, `AICDSS`, `LabIoTGateway`, `AdaptivePlanning`, `Communication`, `SecurityIdentity`, а `{Подпапка}` повторяет структуру `src` (`Entity`, `Repository`, `State`, `Filter`, `Resource`, `Command`, `EventListener`, `Security`). Для кросс-контекстной инфраструктуры (`Ibs\Shared`) — `tests/Ibs/Shared/...` по той же логике.
