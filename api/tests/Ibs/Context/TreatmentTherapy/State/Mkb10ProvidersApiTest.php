<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\TreatmentTherapy\State;

use App\Tests\Support\AuthenticatesUsers;
use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\TreatmentTherapy\Entity\Mkb10;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class Mkb10ProvidersApiTest extends WebTestCase
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

    public function testSearchEndpointReturnsMatchesForShortQuery(): void
    {
        $token = $this->createAuthenticatedClient($this->client, $this->entityManager);

        $mkb10 = new Mkb10();
        $mkb10->setId(930001);
        $mkb10->setMkbCode('K29');
        $mkb10->setMkbName('Гастрит и дуоденит');
        $this->entityManager->persist($mkb10);
        $this->entityManager->flush();

        $this->client->request(
            'GET',
            '/api/mkb10/search?q=K29',
            server: array_merge($this->authHeader($token), ['HTTP_ACCEPT' => 'application/json'])
        );

        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode((string) $response->getContent(), true);
        $this->assertNotEmpty($data);
    }

    public function testSearchEndpointReturnsEmptyArrayForTooShortQuery(): void
    {
        $token = $this->createAuthenticatedClient($this->client, $this->entityManager);

        $this->client->request(
            'GET',
            '/api/mkb10/search?q=K',
            server: array_merge($this->authHeader($token), ['HTTP_ACCEPT' => 'application/json'])
        );

        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame([], $data);
    }

    public function testPopularEndpointReturnsSuccessfully(): void
    {
        $token = $this->createAuthenticatedClient($this->client, $this->entityManager);

        $this->client->request(
            'GET',
            '/api/mkb10/popular',
            server: array_merge($this->authHeader($token), ['HTTP_ACCEPT' => 'application/json'])
        );

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }
}
