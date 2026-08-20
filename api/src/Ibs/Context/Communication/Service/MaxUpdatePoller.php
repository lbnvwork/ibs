<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Клиент Long Polling API MAX (GET /updates).
 *
 * Используется для получения входящих событий (bot_started, message_created
 * и т.д.) и, в частности, для автоматического сбора chat_id получателей.
 */
final class MaxUpdatePoller
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $maxApiUrl,
        private readonly string $maxBotToken,
    ) {
    }

    public function isAvailable(): bool
    {
        return '' !== $this->maxApiUrl && '' !== $this->maxBotToken;
    }

    /**
     * Получает обновления через GET /updates (Long Polling).
     *
     * @param string[] $types
     *
     * @return array{updates: array<int, mixed>, marker: string|null}
     */
    public function fetch(?string $marker = null, array $types = [], int $limit = 100, int $timeout = 1): array
    {
        if (!$this->isAvailable()) {
            return ['updates' => [], 'marker' => null];
        }

        $query = [
            'limit' => $limit,
            'timeout' => $timeout,
        ];

        if (null !== $marker && '' !== $marker) {
            $query['marker'] = $marker;
        }

        if ([] !== $types) {
            $query['types'] = implode(',', $types);
        }

        try {
            $response = $this->httpClient->request(
                'GET',
                rtrim($this->maxApiUrl, '/') . '/updates',
                [
                    'query' => $query,
                    'headers' => ['Authorization' => $this->maxBotToken],
                    'timeout' => $timeout + 5,
                ],
            );

            $content = $response->getContent();
        } catch (\Throwable) {
            return ['updates' => [], 'marker' => null];
        }

        $payload = json_decode($content, true);

        if (!\is_array($payload)) {
            return ['updates' => [], 'marker' => null];
        }

        $updates = $payload['updates'] ?? null;
        $marker = $payload['marker'] ?? null;

        return [
            'updates' => \is_array($updates) ? array_values($updates) : [],
            'marker' => \is_string($marker) || \is_int($marker) ? (string) $marker : null,
        ];
    }
}
