<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\PatientManagement\Entity;

use App\Tests\Support\AuthenticatesUsers;
use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\PatientManagement\Entity\CkdStage;
use Ibs\Context\PatientManagement\Entity\DiabetesType;
use Ibs\Context\PatientManagement\Entity\Patient;
use Ibs\Context\PatientManagement\Entity\PatientAnamnesis;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PatientAnamnesisApiTest extends WebTestCase
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
     * СЦ-3.31.8 (security): доступ без JWT к анамнезу → 401 Unauthorized.
     */
    public function testAnamnesisRequiresJwt(): void
    {
        $this->client->request('GET', '/api/patient_anamneses');

        $this->assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    /**
     * СЦ-3.31.1 (позитив): создание анамнеза со справочниками СД + ХБП и шкалами.
     */
    public function testCreateAndReadAnamnesis(): void
    {
        $token = $this->createAuthenticatedClient($this->client, $this->entityManager);
        $patient = $this->createPatient();
        $diabetes = $this->createDiabetesType('СД2', 'Сахарный диабет 2 типа');
        $ckd = $this->createCkdStage('Стадия 2', 'Хроническая болезнь почек, стадия 2');
        $this->entityManager->flush();

        $this->client->request(
            'POST',
            '/api/patient_anamneses',
            server: array_merge($this->authHeader($token), ['CONTENT_TYPE' => 'application/json']),
            content: json_encode([
                'patient' => '/api/patients/'.$patient->getId(),
                'mk' => true,
                'diabetes' => '/api/diabetes_types/'.$diabetes->getId(),
                'strokeIschemic' => true,
                'ckdStage' => '/api/ckd_stages/'.$ckd->getId(),
                'cha2ds2Vasc' => 4,
                'hasBled' => 1,
            ], JSON_THROW_ON_ERROR)
        );

        $this->assertSame(201, $this->client->getResponse()->getStatusCode());
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('id', $data);

        $this->entityManager->clear();
        $anamnesis = $this->entityManager->find(PatientAnamnesis::class, $data['id']);

        $this->assertNotNull($anamnesis);
        $this->assertTrue($anamnesis->getMk());
        $this->assertNotNull($anamnesis->getDiabetes());
        $this->assertSame($diabetes->getId(), $anamnesis->getDiabetes()->getId());
        $this->assertTrue($anamnesis->getStrokeIschemic());
        $this->assertNotNull($anamnesis->getCkdStage());
        $this->assertSame($ckd->getId(), $anamnesis->getCkdStage()->getId());
        $this->assertSame(4, $anamnesis->getCha2ds2Vasc());
        $this->assertSame(1, $anamnesis->getHasBled());
    }

    /**
     * СЦ-3.31.1 (позитив): фильтрация анамнеза по пациенту.
     */
    public function testAnamnesisIsFilterableByPatient(): void
    {
        $token = $this->createAuthenticatedClient($this->client, $this->entityManager);
        $patient = $this->createPatient();
        $this->entityManager->flush();
        $patientId = $patient->getId();

        $this->client->request(
            'POST',
            '/api/patient_anamneses',
            server: array_merge($this->authHeader($token), ['CONTENT_TYPE' => 'application/json']),
            content: json_encode([
                'patient' => '/api/patients/'.$patientId,
                'mk' => true,
            ], JSON_THROW_ON_ERROR)
        );
        $this->assertSame(201, $this->client->getResponse()->getStatusCode());

        $this->client->request(
            'GET',
            '/api/patient_anamneses?patient='.$patientId,
            server: $this->authHeader($token)
        );

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $filtered = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertIsArray($filtered);

        $this->assertCount(1, $filtered);
        $item = $filtered[0];
        $this->assertIsArray($item);
        $this->assertSame('/api/patients/'.$patientId, $item['patient']);

        // фильтр по несуществующему пациенту возвращает пустой список
        $this->client->request(
            'GET',
            '/api/patient_anamneses?patient=999999999',
            server: $this->authHeader($token)
        );

        $empty = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertIsArray($empty);
        $this->assertCount(0, $empty);
    }

    /**
     * СЦ-3.31.5 (негатив): несуществующий справочник СД (IRI) → 400.
     */
    public function testInvalidDiabetesIsRejected(): void
    {
        $token = $this->createAuthenticatedClient($this->client, $this->entityManager);
        $patient = $this->createPatient();
        $this->entityManager->flush();

        $this->client->request(
            'POST',
            '/api/patient_anamneses',
            server: array_merge($this->authHeader($token), ['CONTENT_TYPE' => 'application/json']),
            content: json_encode([
                'patient' => '/api/patients/'.$patient->getId(),
                'diabetes' => '/api/diabetes_types/999999',
            ], JSON_THROW_ON_ERROR)
        );

        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    /**
     * СЦ-3.31.5 (негатив): отрицательная шкала CHA₂DS₂-VASc → 422.
     */
    public function testNegativeScaleIsRejected(): void
    {
        $token = $this->createAuthenticatedClient($this->client, $this->entityManager);
        $patient = $this->createPatient();
        $this->entityManager->flush();

        $this->client->request(
            'POST',
            '/api/patient_anamneses',
            server: array_merge($this->authHeader($token), ['CONTENT_TYPE' => 'application/json']),
            content: json_encode([
                'patient' => '/api/patients/'.$patient->getId(),
                'cha2ds2Vasc' => -1,
            ], JSON_THROW_ON_ERROR)
        );

        $this->assertSame(422, $this->client->getResponse()->getStatusCode());
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

    private function createDiabetesType(string $name, string $fullName): DiabetesType
    {
        $type = new DiabetesType();
        $type->setName($name);
        $type->setFullName($fullName);
        $this->entityManager->persist($type);

        return $type;
    }

    private function createCkdStage(string $name, string $fullName): CkdStage
    {
        $stage = new CkdStage();
        $stage->setName($name);
        $stage->setFullName($fullName);
        $this->entityManager->persist($stage);

        return $stage;
    }
}
