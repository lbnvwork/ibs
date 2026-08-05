<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\TreatmentTherapy\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\PatientManagement\Entity\Patient;
use Ibs\Context\TreatmentTherapy\Entity\Drug;
use Ibs\Context\TreatmentTherapy\Entity\Mkb10;
use Ibs\Context\TreatmentTherapy\Entity\Treatment;
use Ibs\Context\TreatmentTherapy\Repository\Mkb10Repository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class Mkb10RepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private Mkb10Repository $repository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->getConnection()->beginTransaction();
        $this->repository = static::getContainer()->get(Mkb10Repository::class);
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

    public function testFindPopularActiveDiagnosesOrdersByActiveTreatmentCount(): void
    {
        $popular = $this->createMkb10(910001, 'I80.0', 'Флебит и тромбофлебит');
        $rare = $this->createMkb10(910002, 'I26.0', 'Лёгочная эмболия');
        $drug = new Drug();
        $drug->setNominative('Варфарин');
        $this->entityManager->persist($drug);

        // two active treatments referencing the "popular" diagnosis, one for "rare"
        $this->createActiveTreatment($drug, $popular);
        $this->createActiveTreatment($drug, $popular);
        $this->createActiveTreatment($drug, $rare);

        $this->entityManager->flush();

        $results = $this->repository->findPopularActiveDiagnoses(10);
        $ids = array_map(static fn (Mkb10 $m) => $m->getId(), $results);

        $this->assertNotEmpty($ids);
        $popularPos = array_search($popular->getId(), $ids, true);
        $rarePos = array_search($rare->getId(), $ids, true);
        $this->assertNotFalse($popularPos);
        $this->assertNotFalse($rarePos);
        $this->assertLessThan($rarePos, $popularPos, 'Diagnosis with more active treatments must rank first.');
    }

    public function testSearchByCodeOrNameMatchesCodeOrName(): void
    {
        $this->createMkb10(910003, 'J45.0', 'Астма с преобладанием аллергического компонента');
        $this->entityManager->flush();

        $byCode = $this->repository->searchByCodeOrName('J45');
        $byName = $this->repository->searchByCodeOrName('астма');

        $this->assertNotEmpty($byCode);
        $this->assertSame('J45.0', $byCode[0]['mkb_code']);

        $this->assertNotEmpty($byName);
        $this->assertSame('J45.0', $byName[0]['mkb_code']);
    }

    private function createMkb10(int $id, string $code, string $name): Mkb10
    {
        $mkb10 = new Mkb10();
        $mkb10->setId($id);
        $mkb10->setMkbCode($code);
        $mkb10->setMkbName($name);
        $this->entityManager->persist($mkb10);

        return $mkb10;
    }

    private function createActiveTreatment(Drug $drug, Mkb10 $mkb10): Treatment
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
        $treatment->setMkb10($mkb10);
        $treatment->setDiagnosis('Диагноз');
        $treatment->setDiagnosisCode($mkb10->getMkbCode());
        $treatment->setMnoFrom(2.0);
        $treatment->setMnoTo(3.0);
        $treatment->setBegDt(new \DateTime('-5 days'));
        $this->entityManager->persist($treatment);

        return $treatment;
    }
}
