<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\SecurityIdentity\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Ibs\Context\SecurityIdentity\Command\CreateUserCommand;
use Ibs\Context\SecurityIdentity\Entity\User;
use PHPUnit\Framework\TestCase;
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
}
