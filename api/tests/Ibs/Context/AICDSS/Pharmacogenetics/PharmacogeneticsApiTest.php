<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\AICDSS\Pharmacogenetics;

use App\Tests\Support\AuthenticatesUsers;
use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\AICDSS\Entity\GeneticMarker;
use Ibs\Context\AICDSS\Entity\GeneticMarkerValue;
use Ibs\Context\AICDSS\Entity\MarkerDrugRelation;
use Ibs\Context\AICDSS\Entity\PatientGeneticResult;
use Ibs\Context\PatientManagement\Entity\Patient;
use Ibs\Context\TreatmentTherapy\Entity\Drug;
use Ibs\Context\TreatmentTherapy\Entity\Treatment;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Regression test: same root cause as DosageRecommendationApiTest, this
 * resource's Dto/ folder was excluded from services.yaml scanning too.
 */
class PharmacogeneticsApiTest extends WebTestCase
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

    public function testEndpointReturnsMarkersLinkedToPatientsLatestDrug(): void
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
        $treatment->setBegDt(new \DateTime('-5 days'));
        $this->entityManager->persist($treatment);

        $marker = new GeneticMarker();
        $marker->setGeneSymbol('CYP2C9_2');
        $marker->setFullName('Цитохром P450 2C9, аллель *2');
        $this->entityManager->persist($marker);

        $value = new GeneticMarkerValue();
        $value->setMarker($marker);
        $value->setValue('CC');
        $value->setLabel('CC (норма)');
        $this->entityManager->persist($value);

        $relation = new MarkerDrugRelation();
        $relation->setMarker($marker);
        $relation->setDrug($drug);
        $this->entityManager->persist($relation);

        $result = new PatientGeneticResult();
        $result->setPatient($patient);
        $result->setMarker($marker);
        $result->setMarkerValue($value);
        $this->entityManager->persist($result);

        $this->entityManager->flush();

        $this->client->request(
            'GET',
            '/api/patients/'.$patient->getId().'/pharmacogenetics',
            server: array_merge($this->authHeader($token), ['HTTP_ACCEPT' => 'application/json'])
        );

        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode((string) $response->getContent(), true);
        $this->assertNotEmpty($data['markers']);
        $this->assertSame('CYP2C9_2', $data['markers'][0]['geneSymbol']);
    }
}
