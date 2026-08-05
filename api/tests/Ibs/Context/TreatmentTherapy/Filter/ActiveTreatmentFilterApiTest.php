<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\TreatmentTherapy\Filter;

use App\Tests\Support\AuthenticatesUsers;
use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\PatientManagement\Entity\Patient;
use Ibs\Context\TreatmentTherapy\Entity\Drug;
use Ibs\Context\TreatmentTherapy\Entity\Treatment;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ActiveTreatmentFilterApiTest extends WebTestCase
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

    public function testActiveTrueReturnsOnlyOpenTreatments(): void
    {
        $token = $this->createAuthenticatedClient($this->client, $this->entityManager);

        $drug = new Drug();
        $drug->setNominative('Варфарин');
        $this->entityManager->persist($drug);

        $active = $this->createTreatment($drug, null);
        $finished = $this->createTreatment($drug, new \DateTime('-1 day'));
        $this->entityManager->flush();

        $this->client->request(
            'GET',
            '/api/treatments?active=true',
            server: array_merge($this->authHeader($token), ['HTTP_ACCEPT' => 'application/json'])
        );

        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode((string) $response->getContent(), true);
        $ids = array_column($data, 'id');

        $this->assertContains($active->getId(), $ids);
        $this->assertNotContains($finished->getId(), $ids);
    }

    private function createTreatment(Drug $drug, ?\DateTimeInterface $realEndDt): Treatment
    {
        $patient = new Patient();
        $patient->setFirstname('Тест');
        $patient->setLastname('Тестов');
        $patient->setBirthday(new \DateTime('1985-01-01'));
        $patient->setSmsPhone('8(900)000-00-00');
        $this->entityManager->persist($patient);

        $treatment = new Treatment();
        $treatment->setPatient($patient);
        $treatment->setDrug($drug);
        $treatment->setDiagnosis('Диагноз');
        $treatment->setDiagnosisCode('I80');
        $treatment->setMnoFrom(2.0);
        $treatment->setMnoTo(3.0);
        $treatment->setBegDt(new \DateTime('-10 days'));
        $treatment->setRealEndDt($realEndDt);
        $this->entityManager->persist($treatment);

        return $treatment;
    }
}
