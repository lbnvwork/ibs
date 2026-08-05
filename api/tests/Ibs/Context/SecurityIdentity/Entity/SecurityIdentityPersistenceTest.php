<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\SecurityIdentity\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\PatientManagement\Entity\Hospital;
use Ibs\Context\PatientManagement\Entity\PhoneType;
use Ibs\Context\SecurityIdentity\Entity\MedicalPersonnel;
use Ibs\Context\SecurityIdentity\Entity\MedicalPersonnelPhone;
use Ibs\Context\SecurityIdentity\Entity\User;
use Ibs\Context\SecurityIdentity\Entity\UserForHospital;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SecurityIdentityPersistenceTest extends KernelTestCase
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

    public function testUserAlwaysHasRoleUserEvenWithoutExplicitRoles(): void
    {
        $user = new User();
        $user->setLogin('role.default.user');
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $id = $user->getId();
        $this->entityManager->clear();

        $reloaded = $this->entityManager->find(User::class, $id);
        $this->assertContains('ROLE_USER', $reloaded->getRoles());
    }

    public function testMedicalPersonnelPhoneIsLinkedToPersonAndPhoneType(): void
    {
        $hospital = new Hospital();
        $hospital->setName('ЦРБ');
        $this->entityManager->persist($hospital);

        $personnel = new MedicalPersonnel();
        $personnel->setName('Др. Кузнецова');
        $personnel->setHospital($hospital);
        $this->entityManager->persist($personnel);

        $phoneType = new PhoneType();
        $phoneType->setName('Рабочий');
        $this->entityManager->persist($phoneType);

        $phone = new MedicalPersonnelPhone();
        $phone->setPerson($personnel);
        $phone->setPhoneType($phoneType);
        $phone->setNumber('8(800)555-35-35');
        $this->entityManager->persist($phone);

        $this->entityManager->flush();
        $id = $phone->getId();
        $this->entityManager->clear();

        $reloaded = $this->entityManager->find(MedicalPersonnelPhone::class, $id);
        $this->assertSame('8(800)555-35-35', $reloaded->getNumber());
        $this->assertSame('Др. Кузнецова', $reloaded->getPerson()->getName());
    }

    public function testUserForHospitalLinksUserAndHospitalWithPermissions(): void
    {
        $hospital = new Hospital();
        $hospital->setName('ЦРБ');
        $this->entityManager->persist($hospital);

        $user = new User();
        $user->setLogin('hospital.user');
        $this->entityManager->persist($user);

        $link = new UserForHospital();
        $link->setUser($user);
        $link->setHospital($hospital);
        $link->setPermissions(7);
        $this->entityManager->persist($link);

        $this->entityManager->flush();
        $id = $link->getId();
        $this->entityManager->clear();

        $reloaded = $this->entityManager->find(UserForHospital::class, $id);
        $this->assertSame(7, $reloaded->getPermissions());
        $this->assertSame('hospital.user', $reloaded->getUser()->getLogin());
    }
}
