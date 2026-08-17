<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Service;

use Ibs\Context\Communication\Model\NotificationMessage;
use Ibs\Context\Communication\Model\Recipient;
use Ibs\Context\Communication\Model\SendResult;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Адаптер отправки уведомлений через мессенджер MAX.
 *
 * POST https://platform-api.max.ru/messages

 *   query:  chat_id = $address
 *   headers: Authorization: Bearer {MAX_BOT_TOKEN}
 *            Content-Type: application/json
 *   body:   {"text": "<plain text, <= 4000 символов>"}
 */
final class MaxChannel implements ChannelInterface
{
    private const CHANNEL_TYPE = 'max';

    private const MAX_TEXT_LENGTH = 4000;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $maxApiUrl,
        private readonly string $maxBotToken,
    ) {
    }

    public function send(Recipient $recipient, string $address, NotificationMessage $message): SendResult
    {
        $chatId = trim($address);

        if ('' === $chatId) {
            return SendResult::failure('MAX chat_id is not configured for the recipient.');
        }

        $text = trim($message->body);
        $text = $this->stripHtml($text);
        $this->assertValidText($text);

        try {
            $response = $this->httpClient->request(
                'POST',
                rtrim($this->maxApiUrl, '/') . '/messages',
                [
                    'query' => ['chat_id' => $chatId],
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->maxBotToken,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => ['text' => $text],
                    'timeout' => 10,
                ],
            );

            $statusCode = $response->getStatusCode();
            $content = $response->getContent(false);
        } catch (TransportExceptionInterface $exception) {
            // Проблема сети/таймаут — временная ошибка, которую NotificationService
            // может повторить с экспоненциальной задержкой.
            return SendResult::failure($exception->getMessage(), retryable: true);
        } catch (\Throwable $exception) {
            return SendResult::failure($exception->getMessage());
        }

        if ($statusCode >= 500) {
            // Временная ошибка на стороне MAX.
            return SendResult::failure(
                \sprintf('MAX API returned HTTP %d.', $statusCode),
                retryable: true,
            );
        }

        if ($statusCode >= 400) {
            // Критическая клиентская ошибка: авторизация/адресат/сообщение.
            return SendResult::failure($this->extractErrorMessage($content, $statusCode));
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            return SendResult::failure(
                \sprintf('MAX API returned unexpected HTTP %d.', $statusCode),
            );
        }

        $payload = $this->decodePayload($content);
        $externalId = $this->extractExternalId($payload);
        $status = strtolower((string) ($payload['status'] ?? $payload['delivery_status'] ?? 'sent'));

        return \in_array($status, ['delivered', 'read'], true)
            ? SendResult::delivered($externalId)
            : SendResult::success($externalId);
    }

    public function isAvailable(): bool
    {
        return '' !== $this->maxApiUrl && '' !== $this->maxBotToken;
    }

    public function getChannelType(): string
    {
        return self::CHANNEL_TYPE;
    }

    private function stripHtml(string $text): string
    {
        // MAX принимает только plain text, поэтому HTML-теги лучше удалить,
        // чем превращать их наличие в ошибку отправки.
        return \strip_tags($text);
    }

    private function assertValidText(string $text): void
    {
        if (\mb_strlen($text) > self::MAX_TEXT_LENGTH) {
            throw new \InvalidArgumentException(
                \sprintf('MAX message text must not exceed %d characters.', self::MAX_TEXT_LENGTH),
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePayload(string $content): array
    {
        try {
            $payload = json_decode($content, true, flags: \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return \is_array($payload) ? $payload : [];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractExternalId(array $payload): ?string
    {
        foreach (['external_id', 'message_id', 'id'] as $key) {
            if (isset($payload[$key]) && '' !== (string) $payload[$key]) {
                return (string) $payload[$key];
            }
        }

        return null;
    }

    private function extractErrorMessage(string $content, int $statusCode): string
    {
        $payload = $this->decodePayload($content);

        if (isset($payload['error']['message'])) {
            return (string) $payload['error']['message'];
        }

        if (isset($payload['error']) && \is_string($payload['error'])) {
            return (string) $payload['error'];
        }

        if (isset($payload['message'])) {
            return (string) $payload['message'];
        }

        return \sprintf('MAX API returned HTTP %d.', $statusCode);
    }
}