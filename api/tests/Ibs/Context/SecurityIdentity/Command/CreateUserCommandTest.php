<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\SecurityIdentity\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Ibs\Context\SecurityIdentity\Command\CreateUserCommand;
use Ibs\Context\SecurityIdentity\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateUserCommandTest extends TestCase
{
    public function testCreatesUser(): void
    {
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturn('hashed-password');

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findOneBy')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);

        $capturedUser = null;
        $em->expects(self::once())
            ->method('persist')
            ->with(self::callback(static function (object $entity) use (&$capturedUser): bool {
                $capturedUser = $entity;

                return $entity instanceof User;
            }));
        $em->expects(self::once())->method('flush');

        $command = new CreateUserCommand($em, $hasher);
        $tester = new CommandTester($command);
        $tester->execute([
            '--login' => 'admin',
            '--password' => 'secret',
            '--role' => ['ROLE_ADMIN'],
        ]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertInstanceOf(User::class, $capturedUser);
        self::assertSame('admin', $capturedUser->getLogin());
        self::assertSame('hashed-password', $capturedUser->getPassword());
        self::assertNotSame('secret', $capturedUser->getPassword());
        self::assertContains('ROLE_ADMIN', $capturedUser->getRoles());
    }

    public function testCreatesMedicalPersonnel(): void
    {
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturn('hashed-password');

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findOneBy')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);

        $capturedUser = null;
        $em->method('persist')->willReturnCallback(static function (object $entity) use (&$capturedUser): void {
            if ($entity instanceof User) {
                $capturedUser = $entity;
            }
        });
        $em->expects(self::once())->method('flush');

        $command = new CreateUserCommand($em, $hasher);
        $tester = new CommandTester($command);
        $tester->execute([
            '--login' => 'doc',
            '--password' => 'p',
            '--role' => ['ROLE_USER'],
            '--name' => 'Иванов Иван',
        ]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertInstanceOf(User::class, $capturedUser);
        self::assertNotNull($capturedUser->getMedicalPersonnel());
        self::assertSame('Иванов Иван', $capturedUser->getMedicalPersonnel()?->getName());
    }

    public function testMultipleRoles(): void
    {
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturn('hashed-password');

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findOneBy')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);

        $capturedUser = null;
        $em->expects(self::once())
            ->method('persist')
            ->with(self::callback(static function (object $entity) use (&$capturedUser): bool {
                $capturedUser = $entity;

                return $entity instanceof User;
            }));
        $em->expects(self::once())->method('flush');

        $command = new CreateUserCommand($em, $hasher);
        $tester = new CommandTester($command);
        $tester->execute([
            '--login' => 'multi',
            '--password' => 'p',
            '--role' => ['ROLE_USER', 'ROLE_ADMIN'],
        ]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertInstanceOf(User::class, $capturedUser);
        self::assertContains('ROLE_USER', $capturedUser->getRoles());
        self::assertContains('ROLE_ADMIN', $capturedUser->getRoles());
    }

    public function testIdempotent(): void
    {
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects(self::never())->method('hashPassword');

        $existing = new User();
        $existing->setLogin('admin');

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findOneBy')->willReturn($existing);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);
        $em->expects(self::never())->method('persist');
        $em->expects(self::never())->method('flush');

        $command = new CreateUserCommand($em, $hasher);
        $tester = new CommandTester($command);
        $tester->execute([
            '--login' => 'admin',
            '--password' => 'secret',
            '--role' => ['ROLE_ADMIN'],
        ]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('уже существует', $tester->getDisplay());
    }

    public function testMissingLoginOrPassword(): void
    {
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects(self::never())->method('hashPassword');

        $repo = $this->createMock(EntityRepository::class);
        $repo->expects(self::never())->method('findOneBy');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);
        $em->expects(self::never())->method('persist');
        $em->expects(self::never())->method('flush');

        $command = new CreateUserCommand($em, $hasher);
        $tester = new CommandTester($command);

        // Без --password
        $tester->execute(['--login' => 'admin', '--role' => ['ROLE_ADMIN']]);
        self::assertSame(Command::INVALID, $tester->getStatusCode());

        // Без --login
        $tester->execute(['--password' => 'secret', '--role' => ['ROLE_ADMIN']]);
        self::assertSame(Command::INVALID, $tester->getStatusCode());
    }
}
