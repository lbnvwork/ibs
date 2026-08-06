<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\TreatmentTherapy\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\PatientManagement\Entity\Patient;
use Ibs\Context\TreatmentTherapy\Entity\Drug;
use Ibs\Context\TreatmentTherapy\Entity\Treatment;
use Ibs\Context\TreatmentTherapy\Entity\TestHistory;
use Ibs\Context\TreatmentTherapy\Repository\TestHistoryRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TestHistoryRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private TestHistoryRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->getConnection()->beginTransaction();
        $this->repository = static::getContainer()->get(TestHistoryRepository::class);
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

    public function testFindLatestByTreatmentIdsReturnsOnlyMostRecentEntryPerTreatment(): void
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
        $treatment->setDiagnosis('Тромбоз');
        $treatment->setDiagnosisCode('I80');
        $treatment->setMnoFrom(2.0);
        $treatment->setMnoTo(3.0);
        $treatment->setBegDt(new \DateTime('-30 days'));
        $this->entityManager->persist($treatment);

        $older = $this->createTestHistory($treatment, 1.5, new \DateTime('-5 days'));
        $newer = $this->createTestHistory($treatment, 2.5, new \DateTime('-1 day'));

        $this->entityManager->flush();

        $results = $this->repository->findLatestByTreatmentIds([$treatment->getId()]);

        $this->assertCount(1, $results);
        $this->assertSame($newer->getId(), $results[0]->getId());
    }

    public function testFindLatestByTreatmentIdsWithEmptyInputReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->repository->findLatestByTreatmentIds([]));
    }

    private function createTestHistory(Treatment $treatment, float $mno, \DateTimeInterface $creationDt): TestHistory
    {
        $testHistory = new TestHistory();
        $testHistory->setTreatment($treatment);
        $testHistory->setMno($mno);
        $testHistory->setDoze(1.0);

        $reflection = new \ReflectionProperty(TestHistory::class, 'creationDt');
        $reflection->setAccessible(true);
        $reflection->setValue($testHistory, $creationDt);

        $this->entityManager->persist($testHistory);

        return $testHistory;
    }
}
