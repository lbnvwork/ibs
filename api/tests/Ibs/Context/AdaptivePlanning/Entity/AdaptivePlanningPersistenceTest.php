<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\AdaptivePlanning\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\AdaptivePlanning\Entity\Holiday;
use Ibs\Context\AdaptivePlanning\Entity\HospitalTestPlan;
use Ibs\Context\AdaptivePlanning\Entity\Supervisor;
use Ibs\Context\AdaptivePlanning\Entity\TestPlan;
use Ibs\Context\PatientManagement\Entity\Hospital;
use Ibs\Context\PatientManagement\Entity\Patient;
use Ibs\Context\SecurityIdentity\Entity\User;
use Ibs\Context\TreatmentTherapy\Entity\Drug;
use Ibs\Context\TreatmentTherapy\Entity\Treatment;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AdaptivePlanningPersistenceTest extends KernelTestCase
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

    public function testHolidayIsPersistedAndReloaded(): void
    {
        $holiday = new Holiday();
        $holiday->setHMonth(1);
        $holiday->setHDay(1);
        $holiday->setComment('Новый год');
        $this->entityManager->persist($holiday);
        $this->entityManager->flush();
        $id = $holiday->getId();
        $this->entityManager->clear();

        $reloaded = $this->entityManager->find(Holiday::class, $id);
        $this->assertSame(1, $reloaded->getHMonth());
        $this->assertSame(1, $reloaded->getHDay());
        $this->assertSame(2015, $reloaded->getHYear(), 'hYear must default to 2015.');
    }

    public function testTestPlanIsLinkedToTreatment(): void
    {
        $treatment = $this->createTreatment();
        $this->entityManager->flush();

        $plan = new TestPlan();
        $plan->setTreatment($treatment);
        $plan->setTestDt(new \DateTime('+3 days'));
        $plan->setStatus(0);
        $this->entityManager->persist($plan);
        $this->entityManager->flush();
        $id = $plan->getId();
        $this->entityManager->clear();

        $reloaded = $this->entityManager->find(TestPlan::class, $id);
        $this->assertSame($treatment->getId(), $reloaded->getTreatment()->getId());
    }

    public function testHospitalTestPlanAndSupervisorAreLinkedToHospitalAndUser(): void
    {
        $hospital = new Hospital();
        $hospital->setName('ЦРБ');
        $this->entityManager->persist($hospital);

        $user = new User();
        $user->setLogin('supervisor.test');
        $this->entityManager->persist($user);

        $hospitalTestPlan = new HospitalTestPlan();
        $hospitalTestPlan->setHospital($hospital);
        $hospitalTestPlan->setTestDt(new \DateTime('+1 week'));
        $this->entityManager->persist($hospitalTestPlan);

        $supervisor = new Supervisor();
        $supervisor->setUser($user);
        $supervisor->setHospitalTestPlan($hospitalTestPlan);
        $this->entityManager->persist($supervisor);

        $this->entityManager->flush();
        $id = $supervisor->getId();
        $this->entityManager->clear();

        $reloaded = $this->entityManager->find(Supervisor::class, $id);
        $this->assertSame('supervisor.test', $reloaded->getUser()->getLogin());
        $this->assertSame($hospital->getId(), $reloaded->getHospitalTestPlan()->getHospital()->getId());
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
