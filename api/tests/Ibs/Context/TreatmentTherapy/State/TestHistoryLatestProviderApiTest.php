<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\TreatmentTherapy\State;

use App\Tests\Support\AuthenticatesUsers;
use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\PatientManagement\Entity\Patient;
use Ibs\Context\TreatmentTherapy\Entity\Drug;
use Ibs\Context\TreatmentTherapy\Entity\Treatment;
use Ibs\Context\TreatmentTherapy\Entity\TestHistory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TestHistoryLatestProviderApiTest extends WebTestCase
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

    protected function tearDown(): void
    {
        $connection = $this->entityManager->getConnection();
        if ($connection->isTransactionActive()) {
            $connection->rollBack();
        }

        $this->entityManager->close();

        parent::tearDown();
    }

    public function testReturnsOnlyLatestTestHistoryPerRequestedTreatment(): void
    {
        $token = $this->createAuthenticatedClient($this->client, $this->entityManager);

        $patient = new Patient();
        $patient->setFirstname('Тест');
        $patient->setLastname('Тестов');
        $patient->setBirthday(new \DateTime('1985-01-01'));
        $patient->setSmsPhone('8(900)000-00-00');
        $this->entityManager->persist($patient);

        $drug = new Drug();
        $drug->setNominative('Варфарин');
        $this->entityManager->persist($drug);

        $treatment = new Treatment();
        $treatment->setPatient($patient);
        $treatment->setDrug($drug);
        $treatment->setDiagnosis('Диагноз');
        $treatment->setDiagnosisCode('I80');
        $treatment->setMnoFrom(2.0);
        $treatment->setMnoTo(3.0);
        $treatment->setBegDt(new \DateTime('-30 days'));
        $this->entityManager->persist($treatment);

        $older = new TestHistory();
        $older->setTreatment($treatment);
        $older->setMno(1.5);
        $older->setDoze(1.0);
        $this->entityManager->persist($older);
        $this->entityManager->flush();

        $newer = new TestHistory();
        $newer->setTreatment($treatment);
        $newer->setMno(2.5);
        $newer->setDoze(1.5);
        $this->entityManager->persist($newer);
        $this->entityManager->flush();

        $this->client->request(
            'GET',
            '/api/test_histories/latest?treatment[]='.$treatment->getId(),
            server: array_merge($this->authHeader($token), ['HTTP_ACCEPT' => 'application/json'])
        );

        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode((string) $response->getContent(), true);
        $this->assertCount(1, $data);
        $this->assertSame($newer->getId(), $data[0]['id']);
    }
}
