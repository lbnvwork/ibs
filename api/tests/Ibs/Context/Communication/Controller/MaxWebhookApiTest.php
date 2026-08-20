<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\Communication\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Проверяет, что Webhook-эндпоинт MAX публичен (без JWT) и проверяет секрет.
 */
class MaxWebhookApiTest extends WebTestCase
{
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

    public function testWebhookIsPublicAndAcceptsValidSecret(): void
    {
        $this->client->request('POST', '/api/max/webhook', [], [], [
            'HTTP_X-Max-Bot-Api-Secret' => 'test-webhook-secret',
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['update_type' => 'bot_started', 'chat_id' => 42342534, 'payload' => '999011'], JSON_THROW_ON_ERROR));

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testWebhookRejectsInvalidSecret(): void
    {
        $this->client->request('POST', '/api/max/webhook', [], [], [
            'HTTP_X-Max-Bot-Api-Secret' => 'wrong-secret',
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['update_type' => 'bot_started', 'chat_id' => 42342534, 'payload' => '999011'], JSON_THROW_ON_ERROR));

        self::assertSame(403, $this->client->getResponse()->getStatusCode());
    }
}
