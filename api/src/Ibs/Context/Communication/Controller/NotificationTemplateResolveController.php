<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Controller;

use Ibs\Context\Communication\Model\NotificationMessage;
use Ibs\Context\Communication\Service\Exception\TemplateNotFoundException;
use Ibs\Context\Communication\Service\TemplateResolver;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Превью шаблона уведомления — единый источник текста для фронта (3.68/3.69):
 * фронт не дублирует тексты, а резолвит их через тот же TemplateResolver,
 * что и отправка (текст превью и отправки не расходятся).
 *
 * POST /api/notification_templates/resolve  { code, data }  →  { body }
 */
#[AsController]
final class NotificationTemplateResolveController
{
    public function __construct(
        private readonly TemplateResolver $templateResolver,
    ) {
    }

    #[Route('/api/notification_templates/resolve', name: 'notification_template_resolve', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function __invoke(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!\is_array($payload)) {
            return new JsonResponse(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        $code = $payload['code'] ?? null;
        if (!\is_string($code) || '' === \trim($code)) {
            return new JsonResponse(['error' => 'Template code is required.'], Response::HTTP_BAD_REQUEST);
        }

        $data = $this->sanitizeData($payload['data'] ?? null);

        try {
            $resolved = $this->templateResolver->resolve(
                new NotificationMessage(body: '', template: $code, data: $data),
                'max',
            );
        } catch (TemplateNotFoundException) {
            return new JsonResponse(['error' => 'Template not found.'], Response::HTTP_NOT_FOUND);
        }

        // `%comment%` в превью не участвует: убираем хвостовой «. %comment%» из текста.
        $body = \preg_replace('/\.\s*%comment%\s*$/', '', $resolved->body) ?? $resolved->body;

        return new JsonResponse(['body' => $body]);
    }

    /**
     * @return array<string, scalar|null>
     */
    private function sanitizeData(mixed $raw): array
    {
        $data = [];
        if (\is_array($raw)) {
            foreach ($raw as $key => $value) {
                if (\is_string($key) && (\is_scalar($value) || null === $value)) {
                    $data[$key] = $value;
                }
            }
        }

        return $data;
    }
}
