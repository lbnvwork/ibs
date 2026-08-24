<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\Communication\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\Communication\Entity\MaxDeepLink;
use Ibs\Context\Communication\Entity\PatientChannelIdentity;
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
        $this->entityManager->persist(new MaxDeepLink(999011, 'token-42'));
        $this->entityManager->flush();

        $this->client->request('POST', '/api/max/webhook', [], [], [
            'HTTP_X-Max-Bot-Api-Secret' => 'test-webhook-secret',
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['update_type' => 'bot_started', 'chat_id' => 42342534, 'payload' => 'token-42'], JSON_THROW_ON_ERROR));

        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $identity = $this->entityManager->getRepository(PatientChannelIdentity::class)
            ->findOneBy(['patientId' => 999011, 'channelType' => 'max']);
        self::assertNotNull($identity);
        self::assertSame('42342534', $identity->getValue());
    }

    public function testWebhookRejectsInvalidSecret(): void
    {
        $this->client->request('POST', '/api/max/webhook', [], [], [
            'HTTP_X-Max-Bot-Api-Secret' => 'wrong-secret',
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['update_type' => 'bot_started', 'chat_id' => 42342534, 'payload' => 'token-42'], JSON_THROW_ON_ERROR));

        self::assertSame(403, $this->client->getResponse()->getStatusCode());
    }
}
