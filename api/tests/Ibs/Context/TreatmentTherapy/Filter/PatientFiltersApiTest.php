<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\TreatmentTherapy\Filter;

use App\Tests\Support\AuthenticatesUsers;
use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\PatientManagement\Entity\Patient;
use Ibs\Context\TreatmentTherapy\Entity\Drug;
use Ibs\Context\TreatmentTherapy\Entity\DrugGroup;
use Ibs\Context\TreatmentTherapy\Entity\Mkb10;
use Ibs\Context\TreatmentTherapy\Entity\Treatment;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Covers the three custom Patient ApiFilters, all of which match against the
 * patient's latest (max begDt) currently-active treatment.
 */
class PatientFiltersApiTest extends WebTestCase
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

    public function testDiagnosisCodeFilterMatchesPatientsByLatestActiveTreatmentDiagnosis(): void
    {
        $token = $this->createAuthenticatedClient($this->client, $this->entityManager);

        $group = new DrugGroup();
        $group->setName('Антикоагулянты');
        $this->entityManager->persist($group);

        $drug = new Drug();
        $drug->setNominative('Варфарин');
        $drug->setGroup($group);
        $this->entityManager->persist($drug);

        $mkb10 = new Mkb10();
        $mkb10->setId(920001);
        $mkb10->setMkbCode('I80');
        $mkb10->setMkbName('Флебит и тромбофлебит');
        $this->entityManager->persist($mkb10);

        $matching = new Patient();
        $matching->setFirstname('Совп');
        $matching->setLastname('Адающий');
        $matching->setBirthday(new \DateTime('1985-01-01'));
        $matching->setSmsPhone('8(900)000-00-01');
        $this->entityManager->persist($matching);

        $treatment = new Treatment();
        $treatment->setPatient($matching);
        $treatment->setDrug($drug);
        $treatment->setMkb10($mkb10);
        $treatment->setDiagnosis('Диагноз');
        $treatment->setDiagnosisCode('I80');
        $treatment->setMnoFrom(2.0);
        $treatment->setMnoTo(3.0);
        $treatment->setBegDt(new \DateTime('-5 days'));
        $this->entityManager->persist($treatment);

        $nonMatching = new Patient();
        $nonMatching->setFirstname('Другой');
        $nonMatching->setLastname('Пациент');
        $nonMatching->setBirthday(new \DateTime('1985-01-01'));
        $nonMatching->setSmsPhone('8(900)000-00-02');
        $this->entityManager->persist($nonMatching);

        $this->entityManager->flush();

        $this->client->request(
            'GET',
            '/api/patients?diagnosisCode=I80',
            server: array_merge($this->authHeader($token), ['HTTP_ACCEPT' => 'application/json'])
        );

        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $ids = array_column(json_decode((string) $response->getContent(), true), 'id');
        $this->assertContains($matching->getId(), $ids);
        $this->assertNotContains($nonMatching->getId(), $ids);
    }

    public function testDrugFilterMatchesPatientsByLatestActiveTreatmentDrug(): void
    {
        $token = $this->createAuthenticatedClient($this->client, $this->entityManager);

        $drug = new Drug();
        $drug->setNominative('Варфарин');
        $this->entityManager->persist($drug);

        $otherDrug = new Drug();
        $otherDrug->setNominative('Ривароксабан');
        $this->entityManager->persist($otherDrug);

        $matching = $this->createPatientWithTreatment($drug, 'I80');
        $nonMatching = $this->createPatientWithTreatment($otherDrug, 'I80');
        $this->entityManager->flush();

        $this->client->request(
            'GET',
            '/api/patients?drug='.$drug->getId(),
            server: array_merge($this->authHeader($token), ['HTTP_ACCEPT' => 'application/json'])
        );

        $ids = array_column(json_decode((string) $this->client->getResponse()->getContent(), true), 'id');
        $this->assertContains($matching->getId(), $ids);
        $this->assertNotContains($nonMatching->getId(), $ids);
    }

    public function testDrugGroupFilterMatchesPatientsByLatestActiveTreatmentDrugGroup(): void
    {
        $token = $this->createAuthenticatedClient($this->client, $this->entityManager);

        $group = new DrugGroup();
        $group->setName('Антикоагулянты');
        $this->entityManager->persist($group);

        $otherGroup = new DrugGroup();
        $otherGroup->setName('Прочее');
        $this->entityManager->persist($otherGroup);

        $drug = new Drug();
        $drug->setNominative('Варфарин');
        $drug->setGroup($group);
        $this->entityManager->persist($drug);

        $otherDrug = new Drug();
        $otherDrug->setNominative('Аспирин');
        $otherDrug->setGroup($otherGroup);
        $this->entityManager->persist($otherDrug);

        $matching = $this->createPatientWithTreatment($drug, 'I80');
        $nonMatching = $this->createPatientWithTreatment($otherDrug, 'I80');
        $this->entityManager->flush();

        $this->client->request(
            'GET',
            '/api/patients?drugGroup='.$group->getId(),
            server: array_merge($this->authHeader($token), ['HTTP_ACCEPT' => 'application/json'])
        );

        $ids = array_column(json_decode((string) $this->client->getResponse()->getContent(), true), 'id');
        $this->assertContains($matching->getId(), $ids);
        $this->assertNotContains($nonMatching->getId(), $ids);
    }

    private function createPatientWithTreatment(Drug $drug, string $diagnosisCode): Patient
    {
        static $counter = 0;
        ++$counter;

        $patient = new Patient();
        $patient->setFirstname('Пациент');
        $patient->setLastname('Номер'.$counter);
        $patient->setBirthday(new \DateTime('1985-01-01'));
        $patient->setSmsPhone(sprintf('8(900)000-%02d-%02d', $counter, $counter));
        $this->entityManager->persist($patient);

        $treatment = new Treatment();
        $treatment->setPatient($patient);
        $treatment->setDrug($drug);
        $treatment->setDiagnosis('Диагноз');
        $treatment->setDiagnosisCode($diagnosisCode);
        $treatment->setMnoFrom(2.0);
        $treatment->setMnoTo(3.0);
        $treatment->setBegDt(new \DateTime('-5 days'));
        $this->entityManager->persist($treatment);

        return $patient;
    }
}
