<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\LabIoTGateway\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\LabIoTGateway\Entity\PatientVitals;
use Ibs\Context\LabIoTGateway\Entity\PatientVitalsLatest;
use Ibs\Context\LabIoTGateway\Service\PatientVitalsSyncService;
use Ibs\Context\PatientManagement\Entity\Patient;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class VitalsCreatinineTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
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

    /**
     * СЦ-3.31.12 (негатив): креатинин −5 → отклонён (Positive/Range > 0).
     */
    public function testCreatinineNegativeIsRejected(): void
    {
        $vitals = $this->createVitalsWithCreatinine(-5.0);

        $violations = $this->validator->validate($vitals);
        $paths = array_map(
            static fn ($v) => $v->getPropertyPath(),
            iterator_to_array($violations)
        );

        $this->assertContains('creatinine', $paths);
    }

    /**
     * СЦ-3.31.12 (позитив): креатинин 134 → сохранён (float).
     */
    public function testCreatininePositiveIsValid(): void
    {
        $vitals = $this->createVitalsWithCreatinine(134.0);
        $this->assertCount(0, $this->validator->validate($vitals));
    }

    /**
     * СЦ-3.31.12 (граница): пусто → creatinine = null (при наличии другого показателя).
     */
    public function testCreatinineNullIsAllowedWhenAnotherVitalSignIsPresent(): void
    {
        $vitals = new PatientVitals();
        $vitals->setPatient(new Patient());
        $vitals->setHeartRate(72);
        $vitals->setCreatinine(null);

        $this->assertCount(0, $this->validator->validate($vitals));
    }

    /**
     * СЦ-3.31.12 (зеркало): креатинин дублируется в PatientVitalsLatest (как weight).
     */
    public function testCreatinineMirrorsToLatest(): void
    {
        $patient = $this->createPatient();
        $this->entityManager->flush();

        $vitals = new PatientVitals();
        $vitals->setPatient($patient);
        $vitals->setCreatinine(134.0);
        $this->entityManager->persist($vitals);
        $this->entityManager->flush();

        /** @var PatientVitalsSyncService $sync */
        $sync = static::getContainer()->get(PatientVitalsSyncService::class);
        $sync->syncFromVitals($vitals);

        $latest = $this->entityManager->getRepository(PatientVitalsLatest::class)
            ->findOneBy(['patient' => $patient]);

        $this->assertNotNull($latest, 'PatientVitalsSyncService must mirror creatinine into PatientVitalsLatest.');
        $this->assertSame(134.0, $latest->getCreatinine());
    }

    private function createVitalsWithCreatinine(?float $creatinine): PatientVitals
    {
        $vitals = new PatientVitals();
        $vitals->setPatient(new Patient());
        $vitals->setCreatinine($creatinine);

        return $vitals;
    }

    private function createPatient(): Patient
    {
        $patient = new Patient();
        $patient->setFirstname('Тест');
        $patient->setLastname('Тестов');
        $patient->setBirthday(new \DateTime('1985-01-01'));
        $patient->setSmsPhone('8(900)000-00-00');
        $this->entityManager->persist($patient);

        return $patient;
    }
}
