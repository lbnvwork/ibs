<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\AICDSS\Entity;

use App\Tests\Support\AuthenticatesUsers;
use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\AICDSS\Entity\GeneticMarker;
use Ibs\Context\AICDSS\Entity\GeneticMarkerValue;
use Ibs\Context\AICDSS\Entity\PatientGeneticResult;
use Ibs\Context\PatientManagement\Entity\Patient;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PatientGeneticResultApiTest extends WebTestCase
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

    public function testValidResultIsPersistedWithCreatedByFromAuthenticatedUser(): void
    {
        $token = $this->createAuthenticatedClient($this->client, $this->entityManager, login: 'geneticist.smirnov');

        [$patient, $marker, $value] = $this->seedMarkerAndPatient();
        $this->entityManager->flush();

        $this->client->request(
            'POST',
            '/api/patient_genetic_results',
            server: array_merge($this->authHeader($token), ['CONTENT_TYPE' => 'application/json']),
            content: json_encode([
                'patient' => '/api/patients/'.$patient->getId(),
                'marker' => '/api/genetic_markers/'.$marker->getId(),
                'markerValue' => '/api/genetic_marker_values/'.$value->getId(),
            ])
        );

        $response = $this->client->getResponse();
        $this->assertSame(201, $response->getStatusCode());

        $data = json_decode((string) $response->getContent(), true);
        $this->entityManager->clear();
        $result = $this->entityManager->find(PatientGeneticResult::class, $data['id']);

        $this->assertNotNull($result);
        $this->assertSame('geneticist.smirnov', $result->getCreatedBy());
        $this->assertSame('geneticist.smirnov', $result->getUpdatedBy());
    }

    public function testMarkerValueNotBelongingToMarkerIsRejected(): void
    {
        $token = $this->createAuthenticatedClient($this->client, $this->entityManager);

        [$patient, $marker] = $this->seedMarkerAndPatient();

        $otherMarker = new GeneticMarker();
        $otherMarker->setGeneSymbol('OTHER');
        $otherMarker->setFullName('Другой маркер');
        $this->entityManager->persist($otherMarker);

        $otherValue = new GeneticMarkerValue();
        $otherValue->setMarker($otherMarker);
        $otherValue->setValue('XX');
        $otherValue->setLabel('XX');
        $this->entityManager->persist($otherValue);
        $this->entityManager->flush();

        $this->client->request(
            'POST',
            '/api/patient_genetic_results',
            server: array_merge($this->authHeader($token), ['CONTENT_TYPE' => 'application/json']),
            content: json_encode([
                'patient' => '/api/patients/'.$patient->getId(),
                'marker' => '/api/genetic_markers/'.$marker->getId(),
                'markerValue' => '/api/genetic_marker_values/'.$otherValue->getId(),
            ])
        );

        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testDuplicateResultForSamePatientAndMarkerIsRejected(): void
    {
        $token = $this->createAuthenticatedClient($this->client, $this->entityManager);

        [$patient, $marker, $value] = $this->seedMarkerAndPatient();

        $existing = new PatientGeneticResult();
        $existing->setPatient($patient);
        $existing->setMarker($marker);
        $existing->setMarkerValue($value);
        $this->entityManager->persist($existing);
        $this->entityManager->flush();

        $this->client->request(
            'POST',
            '/api/patient_genetic_results',
            server: array_merge($this->authHeader($token), ['CONTENT_TYPE' => 'application/json']),
            content: json_encode([
                'patient' => '/api/patients/'.$patient->getId(),
                'marker' => '/api/genetic_markers/'.$marker->getId(),
                'markerValue' => '/api/genetic_marker_values/'.$value->getId(),
            ])
        );

        $this->assertSame(409, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @return array{0: Patient, 1: GeneticMarker, 2: GeneticMarkerValue}
     */
    private function seedMarkerAndPatient(): array
    {
        $patient = new Patient();
        $patient->setFirstname('Тест');
        $patient->setLastname('Тестов');
        $patient->setBirthday(new \DateTime('1985-01-01'));
        $patient->setSmsPhone('8(900)000-00-00');
        $this->entityManager->persist($patient);

        $marker = new GeneticMarker();
        $marker->setGeneSymbol('CYP2C9_2');
        $marker->setFullName('Цитохром P450 2C9, аллель *2');
        $this->entityManager->persist($marker);

        $value = new GeneticMarkerValue();
        $value->setMarker($marker);
        $value->setValue('CC');
        $value->setLabel('CC (норма)');
        $this->entityManager->persist($value);

        return [$patient, $marker, $value];
    }
}
