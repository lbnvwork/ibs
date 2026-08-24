<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\Communication\Service;

use Ibs\Context\Communication\Model\NotificationMessage;
use Ibs\Context\Communication\Model\Recipient;
use Ibs\Context\Communication\Service\MaxChannel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MaxChannelTest extends TestCase
{
    private const API_URL = 'https://platform-api2.max.ru';

    private const BOT_TOKEN = 'test-token';

    public function testSuccessfulSend(): void
    {
        $captured = null;
        $httpClient = new MockHttpClient(
            static function (string $method, string $url, array $options = []) use (&$captured): MockResponse {
                $captured = ['method' => $method, 'url' => $url, 'options' => $options];

                return new MockResponse(
                    json_encode(['message_id' => 'msg-123', 'status' => 'sent'], JSON_THROW_ON_ERROR),
                    ['http_code' => 200],
                );
            },
        );

        $channel = new MaxChannel($httpClient, self::API_URL, self::BOT_TOKEN);
        $result = $channel->send(
            new Recipient(patientId: 1),
            'chat-42',
            new NotificationMessage(body: '<b>Здравствуйте, пациент!</b>'),
        );

        self::assertTrue($result->success);
        self::assertSame('sent', $result->status);
        self::assertSame('msg-123', $result->externalId);

        self::assertNotNull($captured);
        self::assertSame('POST', $captured['method']);
        self::assertSame('/messages', parse_url($captured['url'], PHP_URL_PATH));

        parse_str((string) parse_url($captured['url'], PHP_URL_QUERY), $query);
        self::assertSame('chat-42', $query['chat_id'] ?? null);

        $authorization = null;
        $contentType = null;
        $headers = $captured['options']['headers'] ?? [];
        self::assertIsArray($headers);
        foreach ($headers as $key => $value) {
            if (is_string($key) && strcasecmp((string) $key, 'Authorization') === 0) {
                $authorization = $value;
            } elseif (is_string($key) && strcasecmp((string) $key, 'Content-Type') === 0) {
                $contentType = $value;
            } elseif (is_string($value) && str_starts_with($value, 'Authorization:')) {
                $authorization = trim(substr($value, strlen('Authorization:')));
            } elseif (is_string($value) && str_starts_with($value, 'Content-Type:')) {
                $contentType = trim(substr($value, strlen('Content-Type:')));
            }
        }

        self::assertSame('test-token', $authorization);
        self::assertSame('application/json', $contentType);
        self::assertSame(10.0, $captured['options']['timeout']);

        $payload = is_callable($captured['options']['body'] ?? null)
            ? ($captured['options']['body'])()
            : ($captured['options']['body'] ?? '');
        self::assertIsString($payload);
        /** @var array<string, mixed> $decodedBody */
        $decodedBody = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
        $text = $decodedBody['text'] ?? null;
        self::assertSame('Здравствуйте, пациент!', $text);

        // HTML-теги удалены перед отправкой.
        self::assertStringNotContainsString('<b', $text);
    }

    public function testDeliveredStatusIsReturnedAsDelivered(): void
    {
        $httpClient = new MockHttpClient(new MockResponse(
            json_encode(['external_id' => 'ext-1', 'status' => 'delivered'], JSON_THROW_ON_ERROR),
            ['http_code' => 200],
        ));

        $channel = new MaxChannel($httpClient, self::API_URL, self::BOT_TOKEN);
        $result = $channel->send(new Recipient(patientId: 1), 'chat-42', new NotificationMessage(body: 'Привет'));

        self::assertTrue($result->success);
        self::assertSame('delivered', $result->status);
        self::assertSame('ext-1', $result->externalId);
    }

    public function testReadStatusIsReturnedAsDelivered(): void
    {
        $httpClient = new MockHttpClient(new MockResponse(
            json_encode(['id' => 'id-1', 'status' => 'read'], JSON_THROW_ON_ERROR),
            ['http_code' => 200],
        ));

        $channel = new MaxChannel($httpClient, self::API_URL, self::BOT_TOKEN);
        $result = $channel->send(new Recipient(patientId: 1), 'chat-42', new NotificationMessage(body: 'Привет'));

        self::assertSame('delivered', $result->status);
        self::assertSame('id-1', $result->externalId);
    }

    public function testExternalIdPreferenceOrder(): void
    {
        $httpClient = new MockHttpClient(new MockResponse(
            json_encode([
                'external_id' => 'external-id',
                'message_id' => 'message-id',
                'id' => 'id',
                'status' => 'sent',
            ], JSON_THROW_ON_ERROR),
            ['http_code' => 200],
        ));

        $channel = new MaxChannel($httpClient, self::API_URL, self::BOT_TOKEN);
        $result = $channel->send(new Recipient(patientId: 1), 'chat-42', new NotificationMessage(body: 'Привет'));

        self::assertSame('external-id', $result->externalId);
    }

    public function testMissingExternalIdIsNull(): void
    {
        $httpClient = new MockHttpClient(new MockResponse(
            json_encode(['status' => 'sent'], JSON_THROW_ON_ERROR),
            ['http_code' => 200],
        ));

        $channel = new MaxChannel($httpClient, self::API_URL, self::BOT_TOKEN);
        $result = $channel->send(new Recipient(patientId: 1), 'chat-42', new NotificationMessage(body: 'Привет'));

        self::assertTrue($result->success);
        self::assertNull($result->externalId);
    }

    public function testHttp401FailureIsNotRetryableAndExtractsErrorMessage(): void
    {
        $httpClient = new MockHttpClient(new MockResponse(
            json_encode(['error' => ['message' => 'Invalid token']], JSON_THROW_ON_ERROR),
            ['http_code' => 401],
        ));

        $channel = new MaxChannel($httpClient, self::API_URL, self::BOT_TOKEN);
        $result = $channel->send(new Recipient(patientId: 1), 'chat-42', new NotificationMessage(body: 'Привет'));

        self::assertFalse($result->success);
        self::assertFalse($result->retryable);
        self::assertSame('Invalid token', $result->errorMessage);
    }

    public function testHttp401FailureExtractsStringError(): void
    {
        $httpClient = new MockHttpClient(new MockResponse(
            json_encode(['error' => 'Forbidden'], JSON_THROW_ON_ERROR),
            ['http_code' => 401],
        ));

        $channel = new MaxChannel($httpClient, self::API_URL, self::BOT_TOKEN);
        $result = $channel->send(new Recipient(patientId: 1), 'chat-42', new NotificationMessage(body: 'Привет'));

        self::assertFalse($result->success);
        self::assertFalse($result->retryable);
        self::assertSame('Forbidden', $result->errorMessage);
    }

    public function testHttp401FailureExtractsMessage(): void
    {
        $httpClient = new MockHttpClient(new MockResponse(
            json_encode(['message' => 'Not authorized'], JSON_THROW_ON_ERROR),
            ['http_code' => 401],
        ));

        $channel = new MaxChannel($httpClient, self::API_URL, self::BOT_TOKEN);
        $result = $channel->send(new Recipient(patientId: 1), 'chat-42', new NotificationMessage(body: 'Привет'));

        self::assertFalse($result->success);
        self::assertFalse($result->retryable);
        self::assertSame('Not authorized', $result->errorMessage);
    }

    public function testHttp500FailureIsRetryable(): void
    {
        $httpClient = new MockHttpClient(new MockResponse('Server error', ['http_code' => 500]));

        $channel = new MaxChannel($httpClient, self::API_URL, self::BOT_TOKEN);
        $result = $channel->send(new Recipient(patientId: 1), 'chat-42', new NotificationMessage(body: 'Привет'));

        self::assertFalse($result->success);
        self::assertTrue($result->retryable);
        self::assertSame('MAX API returned HTTP 500.', $result->errorMessage);
    }

    public function testTransportExceptionIsRetryable(): void
    {
        $exception = new class('Network timeout') extends \RuntimeException implements TransportExceptionInterface {
        };

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects(self::once())
            ->method('request')
            ->willThrowException($exception);

        $channel = new MaxChannel($httpClient, self::API_URL, self::BOT_TOKEN);
        $result = $channel->send(new Recipient(patientId: 1), 'chat-42', new NotificationMessage(body: 'Привет'));

        self::assertFalse($result->success);
        self::assertTrue($result->retryable);
        self::assertSame('Network timeout', $result->errorMessage);
    }

    public function testTextLongerThan4000CharactersThrowsBeforeRequest(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects(self::never())->method('request');

        $channel = new MaxChannel($httpClient, self::API_URL, self::BOT_TOKEN);

        $this->expectException(\InvalidArgumentException::class);

        $channel->send(
            new Recipient(patientId: 1),
            'chat-42',
            new NotificationMessage(body: str_repeat('a', 4001)),
        );
    }

    public function testEmptyAddressReturnsFailureWithoutRequest(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects(self::never())->method('request');

        $channel = new MaxChannel($httpClient, self::API_URL, self::BOT_TOKEN);
        $result = $channel->send(new Recipient(patientId: 1), '   ', new NotificationMessage(body: 'Привет'));

        self::assertFalse($result->success);
        self::assertFalse($result->retryable);
        self::assertSame('MAX chat_id is not configured for the recipient.', $result->errorMessage);
    }

    public function testIsAvailableDependsOnConfiguration(): void
    {

        $httpClient = $this->createStub(HttpClientInterface::class);

        self::assertTrue((new MaxChannel($httpClient, self::API_URL, self::BOT_TOKEN))->isAvailable());
        self::assertFalse((new MaxChannel($httpClient, '', self::BOT_TOKEN))->isAvailable());
        self::assertFalse((new MaxChannel($httpClient, self::API_URL, ''))->isAvailable());
    }

    public function testGetChannelTypeReturnsMax(): void
    {
        $channel = new MaxChannel($this->createStub(HttpClientInterface::class), self::API_URL, self::BOT_TOKEN);

        self::assertSame('max', $channel->getChannelType());
    }
}