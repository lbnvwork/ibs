<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\AICDSS\AiDosage;

use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\AICDSS\AiDosage\Service\DosageRecommendationEngine;
use Ibs\Context\PatientManagement\Entity\Patient;
use Ibs\Context\TreatmentTherapy\Entity\Drug;
use Ibs\Context\TreatmentTherapy\Entity\Treatment;
use Ibs\Context\TreatmentTherapy\Entity\TestHistory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DosageRecommendationEngineTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private DosageRecommendationEngine $engine;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->getConnection()->beginTransaction();
        $this->engine = static::getContainer()->get(DosageRecommendationEngine::class);
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

    public function testUnknownTreatmentReturnsNotFoundExplanation(): void
    {
        $result = $this->engine->recommend(999999);

        $this->assertSame([], $result['variants']);
        $this->assertSame('Лечение не найдено', $result['explanation']);
    }

    public function testTreatmentWithoutTestHistoryReturnsNoDataExplanation(): void
    {
        $treatment = $this->createTreatment(2.0, 3.0);
        $this->entityManager->flush();

        $result = $this->engine->recommend($treatment->getId());

        $this->assertSame([], $result['variants']);
        $this->assertSame('Нет данных МНО для расчёта', $result['explanation']);
    }

    public function testMnoAboveFiveReturnsSpecialWarningWithNoVariants(): void
    {
        $treatment = $this->createTreatment(2.0, 3.0);
        $this->createTestHistory($treatment, 5.5, 2.0);
        $this->entityManager->flush();

        $result = $this->engine->recommend($treatment->getId());

        $this->assertSame([], $result['variants']);
        $this->assertStringContainsString('5.0', $result['explanation']);
    }

    public function testMnoBelowOnePointFiveReturnsSpecialWarningWithNoVariants(): void
    {
        $treatment = $this->createTreatment(2.0, 3.0);
        $this->createTestHistory($treatment, 1.2, 2.0);
        $this->entityManager->flush();

        $result = $this->engine->recommend($treatment->getId());

        $this->assertSame([], $result['variants']);
        $this->assertStringContainsString('1.5', $result['explanation']);
    }

    public function testMnoBelowTargetRangeIncreasesDose(): void
    {
        $treatment = $this->createTreatment(2.0, 3.0);
        $this->createTestHistory($treatment, 1.8, 2.0);
        $this->entityManager->flush();

        $result = $this->engine->recommend($treatment->getId());

        $this->assertSame(2.25, $result['variants'][0]['dose']);
        $this->assertStringContainsString('увеличена', $result['explanation']);
    }

    public function testMnoAboveTargetRangeDecreasesDose(): void
    {
        $treatment = $this->createTreatment(2.0, 3.0);
        $this->createTestHistory($treatment, 3.5, 2.0);
        $this->entityManager->flush();

        $result = $this->engine->recommend($treatment->getId());

        $this->assertSame(1.75, $result['variants'][0]['dose']);
        $this->assertStringContainsString('уменьшена', $result['explanation']);
    }

    public function testMnoWithinTargetRangeLeavesDoseUnchanged(): void
    {
        $treatment = $this->createTreatment(2.0, 3.0);
        $this->createTestHistory($treatment, 2.5, 2.0);
        $this->entityManager->flush();

        $result = $this->engine->recommend($treatment->getId());

        $this->assertSame(2.0, $result['variants'][0]['dose']);
        $this->assertStringContainsString('без изменений', $result['explanation']);
    }

    private function createTreatment(float $mnoFrom, float $mnoTo): Treatment
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
        $treatment->setMnoFrom($mnoFrom);
        $treatment->setMnoTo($mnoTo);
        $treatment->setBegDt(new \DateTime('-10 days'));
        $this->entityManager->persist($treatment);

        return $treatment;
    }

    private function createTestHistory(Treatment $treatment, float $mno, float $doze): TestHistory
    {
        $testHistory = new TestHistory();
        $testHistory->setTreatment($treatment);
        $testHistory->setMno($mno);
        $testHistory->setDoze($doze);
        $this->entityManager->persist($testHistory);

        return $testHistory;
    }
}
