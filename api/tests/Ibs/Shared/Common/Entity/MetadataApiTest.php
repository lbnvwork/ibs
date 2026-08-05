<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Shared\Common\Entity;

use App\Tests\Support\AuthenticatesUsers;
use Doctrine\ORM\EntityManagerInterface;
use Ibs\Shared\Common\Entity\Metadata;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Regression test: Ibs\Shared has no Bundle, so its Entity dir isn't picked up
 * by API Platform's per-bundle discovery and must be listed explicitly in
 * api_platform.yaml's mapping.paths.
 */
class MetadataApiTest extends WebTestCase
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

    public function testMetadataIsReachableThroughTheApi(): void
    {
        $token = $this->createAuthenticatedClient($this->client, $this->entityManager);

        $metadata = new Metadata();
        $metadata->setVersion(7);
        $this->entityManager->persist($metadata);
        $this->entityManager->flush();

        $this->client->request(
            'GET',
            '/api/metadata/'.$metadata->getId(),
            server: array_merge($this->authHeader($token), ['HTTP_ACCEPT' => 'application/json'])
        );

        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode((string) $response->getContent(), true);
        $this->assertSame(7, $data['version']);
    }
}
