<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Controller;

use Ibs\Context\Communication\Service\MaxUpdateProcessor;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Принимает события MAX через Webhook (после регистрации POST /subscriptions).
 *
 * Включается только на деплое: до этого подписка не регистрируется и MAX
 * ничего сюда не отправляет.
 */
#[AsController]
final class MaxWebhookController
{
    public function __construct(
        private readonly MaxUpdateProcessor $processor,
        private readonly string $webhookSecret,
    ) {
    }

    #[Route('/api/max/webhook', name: 'max_webhook', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        if (
            '' === $this->webhookSecret
            || !hash_equals($this->webhookSecret, (string) $request->headers->get('X-Max-Bot-Api-Secret'))
        ) {
            return new JsonResponse(['error' => 'Invalid secret.'], Response::HTTP_FORBIDDEN);
        }

        $update = json_decode($request->getContent(), true);
        if (!\is_array($update)) {
            return new JsonResponse(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        if (null !== $this->processor->process($update)) {
            $this->processor->flush();
        }

        return new JsonResponse(['ok' => true]);
    }
}
