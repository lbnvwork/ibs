<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\Communication\Controller;

use App\Tests\Support\AuthenticatesUsers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class NotificationTemplateResolveTest extends WebTestCase
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

    public function testResolveAnalysisResult(): void
    {
        $token = $this->createAuthenticatedClient($this->client, $this->entityManager, login: 'doctor.resolve');

        $this->client->request(
            'POST',
            '/api/notification_templates/resolve',
            server: array_merge($this->authHeader($token), ['CONTENT_TYPE' => 'application/json']),
            content: json_encode(['code' => 'analysis_result', 'data' => ['mno' => 2.3, 'drug_genitive' => 'варфарина']], JSON_THROW_ON_ERROR)
        );

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());

        /** @var array{body: string} $data */
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame('Ваше МНО - 2.3. Дозировку варфарина оставьте прежней', $data['body']);
    }

    public function testResolveUnknownCodeThrows(): void
    {
        $token = $this->createAuthenticatedClient($this->client, $this->entityManager, login: 'doctor.resolve');

        $this->client->request(
            'POST',
            '/api/notification_templates/resolve',
            server: array_merge($this->authHeader($token), ['CONTENT_TYPE' => 'application/json']),
            content: json_encode(['code' => 'unknown_code', 'data' => []], JSON_THROW_ON_ERROR)
        );

        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testResolveMissingPlaceholderStaysIntact(): void
    {
        $token = $this->createAuthenticatedClient($this->client, $this->entityManager, login: 'doctor.resolve');

        $this->client->request(
            'POST',
            '/api/notification_templates/resolve',
            server: array_merge($this->authHeader($token), ['CONTENT_TYPE' => 'application/json']),
            content: json_encode(['code' => 'analysis_result', 'data' => ['mno' => 2.3]], JSON_THROW_ON_ERROR)
        );

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());

        /** @var array{body: string} $data */
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('%drug_genitive%', $data['body']);
    }

    public function testResolveDropsCommentTail(): void
    {
        $token = $this->createAuthenticatedClient($this->client, $this->entityManager, login: 'doctor.resolve');

        $this->client->request(
            'POST',
            '/api/notification_templates/resolve',
            server: array_merge($this->authHeader($token), ['CONTENT_TYPE' => 'application/json']),
            content: json_encode(['code' => 'appointment_dose', 'data' => ['mno' => 2.3, 'date' => '01.01.2026', 'dose' => 5, 'drug_genitive' => 'варфарина']], JSON_THROW_ON_ERROR)
        );

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());

        /** @var array{body: string} $data */
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertStringNotContainsString('%comment%', $data['body']);
        $this->assertStringContainsString('ВАМ НУЖНО ПРИНИМАТЬ 5 варфарина', $data['body']);
    }

    public function testResolveRequiresAuth(): void
    {
        $this->client->request(
            'POST',
            '/api/notification_templates/resolve',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['code' => 'analysis_result', 'data' => ['mno' => 2.3]], JSON_THROW_ON_ERROR)
        );

        $this->assertSame(401, $this->client->getResponse()->getStatusCode());
    }
}
