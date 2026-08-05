<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\TreatmentTherapy\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\PatientManagement\Entity\Patient;
use Ibs\Context\TreatmentTherapy\Entity\Drug;
use Ibs\Context\TreatmentTherapy\Entity\PatientRequest;
use Ibs\Context\TreatmentTherapy\Entity\Treatment;
use Ibs\Context\TreatmentTherapy\Entity\TreatmentCodeGenerator;
use Ibs\Context\TreatmentTherapy\Entity\TreatmentNote;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TreatmentPersistenceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();

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

    public function testTreatmentNoteAndPatientRequestArePersistedAgainstTreatment(): void
    {
        $treatment = $this->createTreatment();
        $this->entityManager->flush();

        $note = new TreatmentNote();
        $note->setTreatment($treatment);
        $note->setNote('Пациент жалуется на слабость');
        $this->entityManager->persist($note);

        $request = new PatientRequest();
        $request->setTreatment($treatment);
        $request->setReason('Хочет сменить препарат');
        $this->entityManager->persist($request);

        $this->entityManager->flush();
        $noteId = $note->getId();
        $requestId = $request->getId();
        $this->entityManager->clear();

        $reloadedNote = $this->entityManager->find(TreatmentNote::class, $noteId);
        $reloadedRequest = $this->entityManager->find(PatientRequest::class, $requestId);

        $this->assertSame('Пациент жалуется на слабость', $reloadedNote->getNote());
        $this->assertSame($treatment->getId(), $reloadedNote->getTreatment()->getId());
        $this->assertSame('Хочет сменить препарат', $reloadedRequest->getReason());
    }

    public function testTreatmentCodeGeneratorTracksNextCode(): void
    {
        $generator = new TreatmentCodeGenerator();
        $generator->setCode(1000);
        $generator->setGenerate(1);
        $this->entityManager->persist($generator);
        $this->entityManager->flush();
        $id = $generator->getId();
        $this->entityManager->clear();

        $reloaded = $this->entityManager->find(TreatmentCodeGenerator::class, $id);
        $this->assertSame(1000, $reloaded->getCode());
    }

    public function testValidationRejectsMnoRangeWhereFromIsNotLessThanTo(): void
    {
        $treatment = $this->createTreatment();
        $treatment->setMnoFrom(3.0);
        $treatment->setMnoTo(2.0);

        /** @var ValidatorInterface $validator */
        $validator = static::getContainer()->get(ValidatorInterface::class);
        $violations = $validator->validate($treatment);

        $this->assertGreaterThan(0, count($violations));
        $paths = [];
        foreach ($violations as $violation) {
            $paths[] = $violation->getPropertyPath();
        }
        $this->assertContains('mnoFrom', $paths);
    }

    public function testValidationRejectsPlanEndDtBeforeBegDt(): void
    {
        $treatment = $this->createTreatment();
        $treatment->setBegDt(new \DateTime('2026-06-01'));
        $treatment->setPlanEndDt(new \DateTime('2026-05-01'));

        /** @var ValidatorInterface $validator */
        $validator = static::getContainer()->get(ValidatorInterface::class);
        $violations = $validator->validate($treatment);

        $paths = [];
        foreach ($violations as $violation) {
            $paths[] = $violation->getPropertyPath();
        }
        $this->assertContains('planEndDt', $paths);
    }

    public function testValidationPassesForConsistentTreatment(): void
    {
        $treatment = $this->createTreatment();

        /** @var ValidatorInterface $validator */
        $validator = static::getContainer()->get(ValidatorInterface::class);
        $violations = $validator->validate($treatment);

        $this->assertCount(0, $violations);
    }

    private function createTreatment(): Treatment
    {
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

        return $treatment;
    }
}
