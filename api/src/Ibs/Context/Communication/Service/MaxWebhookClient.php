<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Клиент для управления Webhook-подпиской MAX (POST/GET/DELETE /subscriptions).
 */
final class MaxWebhookClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $maxApiUrl,
        private readonly string $maxBotToken,
    ) {
    }

    /**
     * @param string[] $updateTypes
     *
     * @return array<string, mixed>
     */
    public function subscribe(string $url, array $updateTypes, string $secret): array
    {
        return $this->request('POST', '/subscriptions', [
            'json' => [
                'url' => $url,
                'update_types' => $updateTypes,
                'secret' => $secret,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function list(): array
    {
        return $this->request('GET', '/subscriptions');
    }

    /**
     * @return array<string, mixed>
     */
    public function unsubscribe(): array
    {
        return $this->request('DELETE', '/subscriptions');
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $options = []): array
    {
        if ('' === $this->maxApiUrl || '' === $this->maxBotToken) {
            return ['success' => false, 'message' => 'MAX is not configured.'];
        }

        $options['headers'] = ['Authorization' => $this->maxBotToken];

        try {
            $response = $this->httpClient->request($method, rtrim($this->maxApiUrl, '/') . $path, $options);
            $content = $response->getContent();
        } catch (\Throwable $exception) {
            return ['success' => false, 'message' => $exception->getMessage()];
        }

        $payload = json_decode($content, true);

        if (!\is_array($payload)) {
            return ['success' => false, 'message' => 'Invalid response.'];
        }

        /** @var array<string, mixed> $payload */
        return $payload;
    }
}
