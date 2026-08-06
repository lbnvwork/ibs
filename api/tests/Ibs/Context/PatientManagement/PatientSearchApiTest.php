<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\PatientManagement;

use App\Tests\Support\AuthenticatesUsers;
use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\PatientManagement\Entity\Hospital;
use Ibs\Context\PatientManagement\Entity\Patient;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

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

    protected function tearDown(): void
    {
        $connection = $this->entityManager->getConnection();
        if ($connection->isTransactionActive()) {
            $connection->rollBack();
        }

        $this->entityManager->close();

        parent::tearDown();
    }

    public function testLastnameSearchIsCaseInsensitivePartialMatch(): void
    {
        $token = $this->createAuthenticatedClient($this->client, $this->entityManager);

        $matching = $this->createPatient('Иванов');
        $nonMatching = $this->createPatient('Сидоров');
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
        $this->assertNotContains($nonMatching->getId(), $ids);
    }

    public function testHospitalExactFilterReturnsOnlyPatientsOfThatHospital(): void
    {
        $token = $this->createAuthenticatedClient($this->client, $this->entityManager);

        $hospitalA = new Hospital();
        $hospitalA->setName('Больница А');
        $this->entityManager->persist($hospitalA);

        $hospitalB = new Hospital();
        $hospitalB->setName('Больница Б');
        $this->entityManager->persist($hospitalB);

        $inA = $this->createPatient('Пациент1', $hospitalA);
        $inB = $this->createPatient('Пациент2', $hospitalB);
        $this->entityManager->flush();

        $this->client->request(
            'GET',
            '/api/patients?hospital=/api/hospitals/'.$hospitalA->getId(),
            server: array_merge($this->authHeader($token), ['HTTP_ACCEPT' => 'application/json'])
        );

        $ids = array_column(json_decode((string) $this->client->getResponse()->getContent(), true), 'id');
        $this->assertContains($inA->getId(), $ids);
        $this->assertNotContains($inB->getId(), $ids);
    }

    private function createPatient(string $lastname, ?Hospital $hospital = null): Patient
    {
        $patient = new Patient();
        $patient->setFirstname('Тест');
        $patient->setLastname($lastname);
        $patient->setBirthday(new \DateTime('1985-01-01'));
        $patient->setSmsPhone('8(900)000-00-00');
        if ($hospital !== null) {
            $patient->setHospital($hospital);
        }
        $this->entityManager->persist($patient);

        return $patient;
    }
}
