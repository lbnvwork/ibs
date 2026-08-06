<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\PatientManagement\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\PatientManagement\Entity\Hospital;
use Ibs\Context\PatientManagement\Entity\Patient;
use Ibs\Context\PatientManagement\Entity\PatientPhone;
use Ibs\Context\PatientManagement\Entity\PhoneType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PatientPersistenceTest extends KernelTestCase
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

    public function testPatientIsPersistedWithHospitalRelationAndReloadedWithSameData(): void
    {
        $hospital = new Hospital();
        $hospital->setName('Городская клиническая больница №1');
        $this->entityManager->persist($hospital);

        $patient = new Patient();
        $patient->setFirstname('Иван');
        $patient->setLastname('Петров');
        $patient->setBirthday(new \DateTime('1980-05-20'));
        $patient->setSex(1);
        $patient->setSmsPhone('8(900)123-45-67');
        $patient->setHospital($hospital);

        $this->entityManager->persist($patient);
        $this->entityManager->flush();
        $id = $patient->getId();
        $this->entityManager->clear();

        $reloaded = $this->entityManager->find(Patient::class, $id);

        $this->assertNotNull($reloaded);
        $this->assertSame('Иван', $reloaded->getFirstname());
        $this->assertSame('Петров', $reloaded->getLastname());
        $this->assertSame(1, $reloaded->getSex());
        $this->assertNotNull($reloaded->getHospital());
        $this->assertSame('Городская клиническая больница №1', $reloaded->getHospital()->getName());
    }

    public function testPatientPhoneIsLinkedToPatientAndPhoneType(): void
    {
        $patient = new Patient();
        $patient->setFirstname('Мария');
        $patient->setLastname('Сидорова');
        $patient->setBirthday(new \DateTime('1990-01-01'));
        $patient->setSmsPhone('8(900)000-00-00');
        $this->entityManager->persist($patient);

        $phoneType = new PhoneType();
        $phoneType->setName('Мобильный');
        $this->entityManager->persist($phoneType);

        $phone = new PatientPhone();
        $phone->setNumber('8(900)111-22-33');
        $phone->setPerson($patient);
        $phone->setPhoneType($phoneType);
        $this->entityManager->persist($phone);

        $this->entityManager->flush();
        $id = $phone->getId();
        $this->entityManager->clear();

        $reloaded = $this->entityManager->find(PatientPhone::class, $id);

        $this->assertNotNull($reloaded);
        $this->assertSame('8(900)111-22-33', $reloaded->getNumber());
        $this->assertSame('Мобильный', $reloaded->getPhoneType()->getName());
        $this->assertSame($patient->getId(), $reloaded->getPerson()->getId());
    }
}
