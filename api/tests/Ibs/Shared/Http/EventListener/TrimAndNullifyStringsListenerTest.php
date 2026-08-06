<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Shared\Http\EventListener;

use App\Tests\Support\AuthenticatesUsers;
use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\PatientManagement\Entity\Hospital;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TrimAndNullifyStringsListenerTest extends WebTestCase
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

    public function testPaddedStringsAreTrimmedAndBlankStringsBecomeNull(): void
    {
        $token = $this->createAuthenticatedClient($this->client, $this->entityManager);

        $this->client->request(
            'POST',
            '/api/hospitals',
            server: array_merge($this->authHeader($token), ['CONTENT_TYPE' => 'application/json']),
            content: json_encode([
                'name' => '   Городская больница   ',
                'region' => '   ',
            ])
        );

        $response = $this->client->getResponse();
        $this->assertSame(201, $response->getStatusCode());

        $data = json_decode((string) $response->getContent(), true);
        $this->entityManager->clear();
        $hospital = $this->entityManager->find(Hospital::class, $data['id']);

        $this->assertSame('Городская больница', $hospital->getName());
        $this->assertNull($hospital->getRegion(), 'Blank strings must be normalized to null.');
    }
}
