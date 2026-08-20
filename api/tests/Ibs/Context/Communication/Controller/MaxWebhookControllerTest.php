<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\Communication\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\Communication\Controller\MaxWebhookController;
use Ibs\Context\Communication\Repository\PatientChannelIdentityRepository;
use Ibs\Context\Communication\Service\MaxUpdateProcessor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MaxWebhookControllerTest extends TestCase
{
    private function controller(
        string $secret,
        PatientChannelIdentityRepository $identities,
        EntityManagerInterface $entityManager,
    ): MaxWebhookController {
        $processor = new MaxUpdateProcessor($identities, $entityManager);

        return new MaxWebhookController($processor, $secret);
    }

    private function request(string $secret, string $body): Request
    {
        return Request::create('/api/max/webhook', 'POST', [], [], [], [
            'HTTP_X-Max-Bot-Api-Secret' => $secret,
            'CONTENT_TYPE' => 'application/json',
        ], $body);
    }

    public function testValidSecretAndUpdateReturnsOk(): void
    {
        $identities = $this->createStub(PatientChannelIdentityRepository::class);
        $identities->method('findOneByPatientAndChannel')->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist');
        $entityManager->expects(self::once())->method('flush');

        $controller = $this->controller('top-secret', $identities, $entityManager);
        $response = $controller->__invoke($this->request(
            'top-secret',
            json_encode(['update_type' => 'bot_started', 'chat_id' => 'chat-42', 'payload' => '999011'], JSON_THROW_ON_ERROR),
        ));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testInvalidSecretReturnsForbidden(): void
    {
        $identities = $this->createStub(PatientChannelIdentityRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('persist');

        $controller = $this->controller('top-secret', $identities, $entityManager);
        $response = $controller->__invoke($this->request(
            'wrong-secret',
            json_encode(['update_type' => 'bot_started', 'chat_id' => 'chat-42', 'payload' => '999011'], JSON_THROW_ON_ERROR),
        ));

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testInvalidJsonReturnsBadRequest(): void
    {
        $identities = $this->createStub(PatientChannelIdentityRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('persist');

        $controller = $this->controller('top-secret', $identities, $entityManager);
        $response = $controller->__invoke($this->request('top-secret', 'not-json'));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }
}
