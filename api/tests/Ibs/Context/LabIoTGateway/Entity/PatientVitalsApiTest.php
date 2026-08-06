<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\LabIoTGateway\Entity;

use App\Tests\Support\AuthenticatesUsers;
use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\LabIoTGateway\Entity\PatientVitals;
use Ibs\Context\LabIoTGateway\Entity\PatientVitalsLatest;
use Ibs\Context\PatientManagement\Entity\Patient;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PatientVitalsApiTest extends WebTestCase
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

    public function testVitalsWithoutAnyMeasurementIsRejected(): void
    {
        $token = $this->createAuthenticatedClient($this->client, $this->entityManager);
        $patient = $this->createPatient();
        $this->entityManager->flush();

        $this->client->request(
            'POST',
            '/api/patient_vitals',
            server: array_merge($this->authHeader($token), ['CONTENT_TYPE' => 'application/json']),
            content: json_encode([
                'patient' => '/api/patients/'.$patient->getId(),
                'recordDt' => '2026-08-01T08:00:00+00:00',
            ])
        );

        $this->assertSame(422, $this->client->getResponse()->getStatusCode());
    }

    public function testVitalsWithAtLeastOneMeasurementIsPersistedWithAuditAndSyncsToLatest(): void
    {
        $token = $this->createAuthenticatedClient($this->client, $this->entityManager, login: 'nurse.orlova');
        $patient = $this->createPatient();
        $this->entityManager->flush();

        $this->client->request(
            'POST',
            '/api/patient_vitals',
            server: array_merge($this->authHeader($token), ['CONTENT_TYPE' => 'application/json']),
            content: json_encode([
                'patient' => '/api/patients/'.$patient->getId(),
                'recordDt' => '2026-08-01T08:00:00+00:00',
                'heartRate' => 72,
            ])
        );

        $response = $this->client->getResponse();
        $this->assertSame(201, $response->getStatusCode());

        $data = json_decode((string) $response->getContent(), true);
        $this->entityManager->clear();

        $vitals = $this->entityManager->find(PatientVitals::class, $data['id']);
        $this->assertNotNull($vitals);
        $this->assertSame(72, $vitals->getHeartRate());
        $this->assertSame('nurse.orlova', $vitals->getCreatedBy());
        $this->assertSame('nurse.orlova', $vitals->getUpdatedBy());

        $latest = $this->entityManager->getRepository(PatientVitalsLatest::class)->findOneBy(['patient' => $patient->getId()]);
        $this->assertNotNull($latest, 'PatientVitalsListener must sync a PatientVitalsLatest row.');
        $this->assertSame(72, $latest->getHeartRate());
    }

    private function createPatient(): Patient
    {
        $patient = new Patient();
        $patient->setFirstname('Тест');
        $patient->setLastname('Тестов');
        $patient->setBirthday(new \DateTime('1985-01-01'));
        $patient->setSmsPhone('8(900)000-00-00');
        $this->entityManager->persist($patient);

        return $patient;
    }
}
