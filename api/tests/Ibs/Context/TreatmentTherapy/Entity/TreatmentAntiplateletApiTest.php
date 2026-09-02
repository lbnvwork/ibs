<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\TreatmentTherapy\Entity;

use App\Tests\Support\AuthenticatesUsers;
use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\PatientManagement\Entity\Patient;
use Ibs\Context\TreatmentTherapy\Entity\Drug;
use Ibs\Context\TreatmentTherapy\Entity\Treatment;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TreatmentAntiplateletApiTest extends WebTestCase
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

    /**
     * СЦ-3.31.4 (позитив): антиагрегант (IRI Drug) + доза сохраняются.
     */
    public function testAntiplateletDrugAndDozeArePersisted(): void
    {
        $token = $this->createAuthenticatedClient($this->client, $this->entityManager);
        $patient = $this->createPatient();
        $warfarin = $this->createDrug('Варфарин');
        $aspirin = $this->createDrug('Аспирин');
        $this->entityManager->flush();

        $this->client->request(
            'POST',
            '/api/treatments',
            server: array_merge($this->authHeader($token), ['CONTENT_TYPE' => 'application/json']),
            content: json_encode([
                'patient' => '/api/patients/'.$patient->getId(),
                'drug' => '/api/drugs/'.$warfarin->getId(),
                'diagnosis' => 'Фибрилляция предсердий',
                'diagnosisCode' => 'I48',
                'mnoFrom' => 2.0,
                'mnoTo' => 3.0,
                'begDt' => '2026-08-01T08:00:00+00:00',
                'antiplateletDrug' => '/api/drugs/'.$aspirin->getId(),
                'antiplateletDoze' => '100',
            ], JSON_THROW_ON_ERROR)
        );

        $this->assertSame(201, $this->client->getResponse()->getStatusCode());
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('id', $data);

        $this->entityManager->clear();
        $treatment = $this->entityManager->find(Treatment::class, $data['id']);

        $this->assertNotNull($treatment);
        $this->assertNotNull($treatment->getAntiplateletDrug());
        $this->assertSame($aspirin->getId(), $treatment->getAntiplateletDrug()->getId());
        $this->assertSame('100', $treatment->getAntiplateletDoze());
    }

    /**
     * СЦ-3.31.4 (граница): без антиагреганта лечение валидно (antiplateletDrug/Doze = null).
     */
    public function testTreatmentWithoutAntiplateletIsValid(): void
    {
        $token = $this->createAuthenticatedClient($this->client, $this->entityManager);
        $patient = $this->createPatient();
        $warfarin = $this->createDrug('Варфарин');
        $this->entityManager->flush();

        $this->client->request(
            'POST',
            '/api/treatments',
            server: array_merge($this->authHeader($token), ['CONTENT_TYPE' => 'application/json']),
            content: json_encode([
                'patient' => '/api/patients/'.$patient->getId(),
                'drug' => '/api/drugs/'.$warfarin->getId(),
                'diagnosis' => 'Фибрилляция предсердий',
                'diagnosisCode' => 'I48',
                'mnoFrom' => 2.0,
                'mnoTo' => 3.0,
                'begDt' => '2026-08-01T08:00:00+00:00',
            ], JSON_THROW_ON_ERROR)
        );

        $this->assertSame(201, $this->client->getResponse()->getStatusCode());
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('id', $data);

        $this->entityManager->clear();
        $treatment = $this->entityManager->find(Treatment::class, $data['id']);

        $this->assertNotNull($treatment);
        $this->assertNull($treatment->getAntiplateletDrug());
        $this->assertNull($treatment->getAntiplateletDoze());
    }

    /**
     * СЦ-3.31.4 (негатив): несуществующий IRI антиагреганта → 400.
     */
    public function testInvalidAntiplateletDrugIsRejected(): void
    {
        $token = $this->createAuthenticatedClient($this->client, $this->entityManager);
        $patient = $this->createPatient();
        $warfarin = $this->createDrug('Варфарин');
        $this->entityManager->flush();

        $this->client->request(
            'POST',
            '/api/treatments',
            server: array_merge($this->authHeader($token), ['CONTENT_TYPE' => 'application/json']),
            content: json_encode([
                'patient' => '/api/patients/'.$patient->getId(),
                'drug' => '/api/drugs/'.$warfarin->getId(),
                'diagnosis' => 'Фибрилляция предсердий',
                'diagnosisCode' => 'I48',
                'mnoFrom' => 2.0,
                'mnoTo' => 3.0,
                'begDt' => '2026-08-01T08:00:00+00:00',
                'antiplateletDrug' => '/api/drugs/999999',
                'antiplateletDoze' => '100',
            ], JSON_THROW_ON_ERROR)
        );

        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
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

    private function createDrug(string $nominative): Drug
    {
        $drug = new Drug();
        $drug->setNominative($nominative);
        $this->entityManager->persist($drug);

        return $drug;
    }
}
