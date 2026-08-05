<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\SecurityIdentity\Security;

use App\Tests\Support\AuthenticatesUsers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LoginFlowApiTest extends WebTestCase
{
    use AuthenticatesUsers;

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();

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

    public function testValidCredentialsReturnJwtTokenContainingUserId(): void
    {
        $user = $this->createUser($this->entityManager, 'login.flow.user', 'correct-password');

        $this->client->request(
            'POST',
            '/api/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['login' => 'login.flow.user', 'password' => 'correct-password'])
        );

        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode((string) $response->getContent(), true);
        $this->assertArrayHasKey('token', $data);

        [, $payload] = explode('.', $data['token']);
        $decoded = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);

        $this->assertSame($user->getId(), $decoded['id'], 'JwtCreatedListener must embed the user id claim.');
    }

    public function testWrongPasswordReturnsUnauthorized(): void
    {
        $this->createUser($this->entityManager, 'login.flow.wrong', 'correct-password');

        $this->client->request(
            'POST',
            '/api/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['login' => 'login.flow.wrong', 'password' => 'wrong-password'])
        );

        $this->assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function testTokenGrantsAccessToProtectedRouteAndMissingTokenIsRejected(): void
    {
        $token = $this->createAuthenticatedClient($this->client, $this->entityManager, 'login.flow.protected', 'correct-password');

        $this->client->request('GET', '/api/hospitals', server: $this->authHeader($token));
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/api/hospitals');
        $this->assertSame(401, $this->client->getResponse()->getStatusCode());
    }
}
