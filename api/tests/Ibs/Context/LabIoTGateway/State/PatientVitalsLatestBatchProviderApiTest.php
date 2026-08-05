<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\LabIoTGateway\State;

use App\Tests\Support\AuthenticatesUsers;
use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\LabIoTGateway\Entity\PatientVitalsLatest;
use Ibs\Context\PatientManagement\Entity\Patient;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PatientVitalsLatestBatchProviderApiTest extends WebTestCase
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

    public function testBatchEndpointReturnsOnlyLatestVitalsForRequestedPatients(): void
    {
        $token = $this->createAuthenticatedClient($this->client, $this->entityManager);

        $wanted = $this->createPatientWithLatestVitals(80);
        $notWanted = $this->createPatientWithLatestVitals(90);
        $this->entityManager->flush();

        $this->client->request(
            'GET',
            '/api/patient_vitals_latests/batch?patient_id[]='.$wanted->getId(),
            server: array_merge($this->authHeader($token), ['HTTP_ACCEPT' => 'application/json'])
        );

        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode((string) $response->getContent(), true);
        $ids = array_column($data, 'id');

        $wantedLatest = $this->entityManager->getRepository(PatientVitalsLatest::class)->findOneBy(['patient' => $wanted->getId()]);
        $notWantedLatest = $this->entityManager->getRepository(PatientVitalsLatest::class)->findOneBy(['patient' => $notWanted->getId()]);

        $this->assertContains($wantedLatest->getId(), $ids);
        $this->assertNotContains($notWantedLatest->getId(), $ids);
    }

    private function createPatientWithLatestVitals(int $heartRate): Patient
    {
        $patient = new Patient();
        $patient->setFirstname('Тест');
        $patient->setLastname('Тестов'.$heartRate);
        $patient->setBirthday(new \DateTime('1985-01-01'));
        $patient->setSmsPhone('8(900)000-00-00');
        $this->entityManager->persist($patient);

        $latest = new PatientVitalsLatest($patient);
        $latest->setHeartRate($heartRate);
        $this->entityManager->persist($latest);

        return $patient;
    }
}
