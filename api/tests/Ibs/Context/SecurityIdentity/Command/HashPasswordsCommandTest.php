<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\SecurityIdentity\Command;

use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\SecurityIdentity\Entity\User;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class HashPasswordsCommandTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->getConnection()->beginTransaction();

        $application = new Application($client->getKernel());
        $command = $application->find('app:hash-passwords');
        $this->commandTester = new CommandTester($command);
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

    public function testPlainTextPasswordsAreHashed(): void
    {
        $user = new User();
        $user->setLogin('plain.password.user');
        $user->setPassword('plain-text-secret');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $exitCode = $this->commandTester->execute([]);
        $this->assertSame(0, $exitCode);

        $this->entityManager->clear();
        $reloaded = $this->entityManager->find(User::class, $user->getId());

        $this->assertNotSame('plain-text-secret', $reloaded->getPassword());

        /** @var UserPasswordHasherInterface $hasher */
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $this->assertTrue($hasher->isPasswordValid($reloaded, 'plain-text-secret'));
    }

    public function testAlreadyHashedPasswordsAreLeftUntouched(): void
    {
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setLogin('already.hashed.user');
        $user->setPassword($hasher->hashPassword($user, 'already-hashed'));
        $hashedBefore = $user->getPassword();
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->commandTester->execute([]);

        $this->entityManager->clear();
        $reloaded = $this->entityManager->find(User::class, $user->getId());

        $this->assertSame($hashedBefore, $reloaded->getPassword());
        $this->assertStringContainsString('No plain-text passwords found.', $this->commandTester->getDisplay());
    }
}
