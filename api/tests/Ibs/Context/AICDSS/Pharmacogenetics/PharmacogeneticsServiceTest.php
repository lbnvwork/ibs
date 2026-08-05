<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\AICDSS\Pharmacogenetics;

use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\AICDSS\Entity\GeneticMarker;
use Ibs\Context\AICDSS\Entity\GeneticMarkerValue;
use Ibs\Context\AICDSS\Entity\MarkerDrugRelation;
use Ibs\Context\AICDSS\Entity\PatientGeneticResult;
use Ibs\Context\AICDSS\Pharmacogenetics\Service\PharmacogeneticsService;
use Ibs\Context\PatientManagement\Entity\Patient;
use Ibs\Context\TreatmentTherapy\Entity\Drug;
use Ibs\Context\TreatmentTherapy\Entity\Treatment;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PharmacogeneticsServiceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private PharmacogeneticsService $service;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->getConnection()->beginTransaction();
        $this->service = static::getContainer()->get(PharmacogeneticsService::class);
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

    public function testReturnsMarkersLinkedToDrugFromLatestTreatmentWhenDrugIdOmitted(): void
    {
        $drug = new Drug();
        $drug->setNominative('Варфарин');
        $this->entityManager->persist($drug);

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

        $markers = $this->service->getPatientPharmacogenetics($patient->getId());

        $this->assertCount(1, $markers);
        $this->assertSame('CYP2C9_2', $markers[0]['geneSymbol']);
        $this->assertSame($value->getId(), $markers[0]['currentValueId']);
    }

    public function testReturnsEmptyArrayWhenPatientHasNoTreatmentAndNoDrugIdGiven(): void
    {
        $patient = new Patient();
        $patient->setFirstname('Тест');
        $patient->setLastname('БезЛечения');
        $patient->setBirthday(new \DateTime('1985-01-01'));
        $patient->setSmsPhone('8(900)000-00-00');
        $this->entityManager->persist($patient);
        $this->entityManager->flush();

        $markers = $this->service->getPatientPharmacogenetics($patient->getId());

        $this->assertSame([], $markers);
    }
}
