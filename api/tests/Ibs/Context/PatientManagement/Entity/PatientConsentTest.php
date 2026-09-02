<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\PatientManagement\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\PatientManagement\Entity\Patient;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PatientConsentTest extends KernelTestCase
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

    /**
     * СЦ-3.31.2 (позитив): согласие «+» → true, MAX пусто → null.
     */
    public function testConsentTrueAndMaxMessengerNullArePersisted(): void
    {
        $patient = $this->createPatient(true, null);
        $this->entityManager->flush();
        $id = $patient->getId();
        $this->entityManager->clear();

        $reloaded = $this->entityManager->find(Patient::class, $id);

        $this->assertNotNull($reloaded);
        $this->assertTrue($reloaded->getConsent());
        $this->assertNull($reloaded->getMaxMessenger());
    }

    /**
     * СЦ-3.31.2 (негатив/граница): согласие «−» → false, MAX «−» → false.
     */
    public function testConsentFalseAndMaxMessengerFalseArePersisted(): void
    {
        $patient = $this->createPatient(false, false);
        $this->entityManager->flush();
        $id = $patient->getId();
        $this->entityManager->clear();

        $reloaded = $this->entityManager->find(Patient::class, $id);

        $this->assertNotNull($reloaded);
        $this->assertFalse($reloaded->getConsent());
        $this->assertFalse($reloaded->getMaxMessenger());
    }

    /**
     * СЦ-3.31.2 (граница): пусто → consent/maxMessenger = null (дефолт).
     */
    public function testConsentAndMaxMessengerDefaultToNull(): void
    {
        $patient = $this->createPatient(null, null);
        $this->entityManager->flush();
        $id = $patient->getId();
        $this->entityManager->clear();

        $reloaded = $this->entityManager->find(Patient::class, $id);

        $this->assertNotNull($reloaded);
        $this->assertNull($reloaded->getConsent());
        $this->assertNull($reloaded->getMaxMessenger());
    }

    private function createPatient(?bool $consent, ?bool $maxMessenger): Patient
    {
        $patient = new Patient();
        $patient->setFirstname('Тест');
        $patient->setLastname('Тестов');
        $patient->setBirthday(new \DateTime('1985-01-01'));
        $patient->setSmsPhone('8(900)000-00-00');
        $patient->setConsent($consent);
        $patient->setMaxMessenger($maxMessenger);
        $this->entityManager->persist($patient);

        return $patient;
    }
}
