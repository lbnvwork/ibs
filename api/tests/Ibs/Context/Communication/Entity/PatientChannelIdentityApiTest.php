<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\Communication\Entity;

use App\Tests\Support\AuthenticatesUsers;
use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\Communication\Entity\PatientChannelIdentity;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PatientChannelIdentityApiTest extends WebTestCase
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

    public function testCreateContactReturns201(): void
    {
        $token = $this->createAuthenticatedClient($this->client, $this->entityManager);

        $this->client->request(
            'POST',
            '/api/patient_channel_identities',
            server: array_merge($this->authHeader($token), ['CONTENT_TYPE' => 'application/json']),
            content: json_encode([
                'patientId' => 42,
                'channelType' => 'max',
                'value' => '42342534',
            ], JSON_THROW_ON_ERROR),
        );

        $this->assertSame(201, $this->client->getResponse()->getStatusCode());
    }

    public function testDuplicateContactReturns409(): void
    {
        $token = $this->createAuthenticatedClient($this->client, $this->entityManager);

        $body = json_encode([
            'patientId' => 42,
            'channelType' => 'max',
            'value' => '42342534',
        ], JSON_THROW_ON_ERROR);

        $server = array_merge($this->authHeader($token), ['CONTENT_TYPE' => 'application/json']);

        $this->client->request('POST', '/api/patient_channel_identities', server: $server, content: $body);
        $this->assertSame(201, $this->client->getResponse()->getStatusCode());

        $this->client->request('POST', '/api/patient_channel_identities', server: $server, content: $body);
        $this->assertSame(409, $this->client->getResponse()->getStatusCode());

        // Дубликат не создал вторую запись — в БД остался ровно 1 контакт.
        $this->entityManager->clear();
        $count = $this->entityManager->getRepository(PatientChannelIdentity::class)->count([
            'patientId' => 42,
            'channelType' => 'max',
        ]);
        $this->assertSame(1, $count);
    }
}
