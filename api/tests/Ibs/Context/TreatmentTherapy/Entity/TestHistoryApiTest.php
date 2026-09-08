<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\TreatmentTherapy\Entity;

use App\Tests\Support\AuthenticatesUsers;
use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\PatientManagement\Entity\Patient;
use Ibs\Context\TreatmentTherapy\Entity\Drug;
use Ibs\Context\TreatmentTherapy\Entity\TestHistory;
use Ibs\Context\TreatmentTherapy\Entity\Treatment;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TestHistoryApiTest extends WebTestCase
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

    public function testDoze2SerializedAsFloat(): void
    {
        $token = $this->createAuthenticatedClient($this->client, $this->entityManager);

        $treatment = $this->createTreatment();
        $this->entityManager->flush();

        $withAlternation = new TestHistory();
        $withAlternation->setTreatment($treatment);
        $withAlternation->setMno(2.5);
        $withAlternation->setDoze(1.0);
        $withAlternation->setDoze2(2.75);
        $this->entityManager->persist($withAlternation);

        $withoutAlternation = new TestHistory();
        $withoutAlternation->setTreatment($treatment);
        $withoutAlternation->setMno(3.0);
        $withoutAlternation->setDoze(2.0);
        $withoutAlternation->setDoze2(-1.0);
        $this->entityManager->persist($withoutAlternation);

        $this->entityManager->flush();

        $this->client->request(
            'GET',
            '/api/test_histories/'.$withAlternation->getId(),
            server: array_merge($this->authHeader($token), ['HTTP_ACCEPT' => 'application/json'])
        );
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        /** @var array{doze2: float} $data */
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame(2.75, $data['doze2']);

        $this->client->request(
            'GET',
            '/api/test_histories/'.$withoutAlternation->getId(),
            server: array_merge($this->authHeader($token), ['HTTP_ACCEPT' => 'application/json'])
        );
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        /** @var array{doze2: int|float} $data */
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame(-1.0, (float) $data['doze2']);
    }

    private function createTreatment(): Treatment
    {
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
        $treatment->setBegDt(new \DateTime('-10 days'));
        $this->entityManager->persist($treatment);

        return $treatment;
    }
}
