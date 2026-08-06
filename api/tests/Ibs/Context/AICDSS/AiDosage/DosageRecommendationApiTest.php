<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\AICDSS\AiDosage;

use App\Tests\Support\AuthenticatesUsers;
use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\PatientManagement\Entity\Patient;
use Ibs\Context\TreatmentTherapy\Entity\Drug;
use Ibs\Context\TreatmentTherapy\Entity\TestHistory;
use Ibs\Context\TreatmentTherapy\Entity\Treatment;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Regression test: the '/dosage/recommendation' route disappeared because the
 * Dto/ folder holding this #[ApiResource] class was excluded from
 * services.yaml scanning, which API Platform also relies on for discovery.
 */
class DosageRecommendationApiTest extends WebTestCase
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

    public function testRecommendationEndpointReturnsDoseVariantsForTreatmentBelowRange(): void
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
        $treatment->setBegDt(new \DateTime('-10 days'));
        $this->entityManager->persist($treatment);

        $testHistory = new TestHistory();
        $testHistory->setTreatment($treatment);
        $testHistory->setMno(1.8);
        $testHistory->setDoze(2.0);
        $this->entityManager->persist($testHistory);

        $this->entityManager->flush();

        $this->client->request(
            'GET',
            '/api/dosage/recommendation?treatment_id='.$treatment->getId(),
            server: array_merge($this->authHeader($token), ['HTTP_ACCEPT' => 'application/json'])
        );

        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode((string) $response->getContent(), true);
        $this->assertNotEmpty($data['variants']);
        $this->assertStringContainsString('увеличена', $data['explanation']);
    }
}
