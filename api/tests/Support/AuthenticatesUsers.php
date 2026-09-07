<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\SecurityIdentity\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Shared helper for functional tests: nearly every /api/* route requires a JWT
 * (unlike imr, where only LisIntegration needed auth), so this trait avoids
 * repeating the same user+login boilerplate in every test class.
 */
trait AuthenticatesUsers
{
    /** @param list<string> $roles */
    private function createUser(
        EntityManagerInterface $entityManager,
        string $login,
        string $plainPassword,
        array $roles = []
    ): User {
        $user = new User();
        $user->setLogin($login);
        $user->setUserName($login);
        $user->setRoles($roles);

        /** @var UserPasswordHasherInterface $hasher */
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($hasher->hashPassword($user, $plainPassword));

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    private function obtainToken(KernelBrowser $client, string $login, string $plainPassword): string
    {
        $client->request(
            'POST',
            '/api/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['login' => $login, 'password' => $plainPassword], JSON_THROW_ON_ERROR)
        );

        /** @var array{token: string} $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);

        return $data['token'];
    }

    /** @param list<string> $roles */
    private function createAuthenticatedClient(
        KernelBrowser $client,
        EntityManagerInterface $entityManager,
        string $login = 'test.user',
        string $plainPassword = 'test-password-123',
        array $roles = []
    ): string {
        $this->createUser($entityManager, $login, $plainPassword, $roles);

        return $this->obtainToken($client, $login, $plainPassword);
    }

    /** @return array<string, string> */
    private function authHeader(string $token): array
    {
        return ['HTTP_AUTHORIZATION' => 'Bearer '.$token];
    }
}
