<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\TreatmentTherapy\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\PatientManagement\Entity\Patient;
use Ibs\Context\TreatmentTherapy\Entity\Drug;
use Ibs\Context\TreatmentTherapy\Entity\Treatment;
use Ibs\Context\TreatmentTherapy\Repository\TreatmentRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TreatmentRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private TreatmentRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->getConnection()->beginTransaction();
        $this->repository = static::getContainer()->get(TreatmentRepository::class);
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

    public function testGetActivePatientIdsReturnsOnlyPatientsWithOpenTreatment(): void
    {
        $drug = $this->createDrug();

        $activePatient = $this->createPatient('Активный');
        $this->createTreatment($activePatient, $drug, realEndDt: null);

        $finishedPatient = $this->createPatient('Завершённый');
        $this->createTreatment($finishedPatient, $drug, realEndDt: new \DateTime('-1 day'));

        $this->entityManager->flush();

        $result = $this->repository->getActivePatientIds([$activePatient->getId(), $finishedPatient->getId()]);

        $this->assertContains($activePatient->getId(), $result);
        $this->assertNotContains($finishedPatient->getId(), $result);
    }

    public function testGetActivePatientIdsWithEmptyInputReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->repository->getActivePatientIds([]));
    }

    private function createPatient(string $lastname): Patient
    {
        $patient = new Patient();
        $patient->setFirstname('Тест');
        $patient->setLastname($lastname);
        $patient->setBirthday(new \DateTime('1985-01-01'));
        $patient->setSmsPhone('8(900)000-00-00');
        $this->entityManager->persist($patient);

        return $patient;
    }

    private function createDrug(): Drug
    {
        $drug = new Drug();
        $drug->setNominative('Варфарин');
        $this->entityManager->persist($drug);

        return $drug;
    }

    private function createTreatment(Patient $patient, Drug $drug, ?\DateTimeInterface $realEndDt): Treatment
    {
        $treatment = new Treatment();
        $treatment->setPatient($patient);
        $treatment->setDrug($drug);
        $treatment->setDiagnosis('Тромбоз');
        $treatment->setDiagnosisCode('I80');
        $treatment->setMnoFrom(2.0);
        $treatment->setMnoTo(3.0);
        $treatment->setBegDt(new \DateTime('-10 days'));
        $treatment->setRealEndDt($realEndDt);
        $this->entityManager->persist($treatment);

        return $treatment;
    }
}
