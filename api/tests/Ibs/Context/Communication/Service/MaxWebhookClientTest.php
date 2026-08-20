<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\Communication\Service;

use Ibs\Context\Communication\Service\MaxWebhookClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class MaxWebhookClientTest extends TestCase
{
    public function testSubscribeSendsExpectedRequest(): void
    {
        $captured = null;
        $httpClient = new MockHttpClient(
            static function (string $method, string $url, array $options = []) use (&$captured): MockResponse {
                $captured = ['method' => $method, 'url' => $url, 'options' => $options];

                return new MockResponse(json_encode(['success' => true], JSON_THROW_ON_ERROR), ['http_code' => 200]);
            },
        );

        $client = new MaxWebhookClient($httpClient, 'https://platform-api2.max.ru', 'test-token');
        $result = $client->subscribe('https://example.com/api/max/webhook', ['bot_started', 'message_created'], 'secret');

        self::assertSame(['success' => true], $result);
        self::assertNotNull($captured);
        self::assertSame('POST', $captured['method']);
        self::assertSame('/subscriptions', parse_url($captured['url'], PHP_URL_PATH));

        $body = $captured['options']['body'] ?? null;
        self::assertIsString($body);
        $decoded = json_decode($body, true);
        self::assertIsArray($decoded);
        self::assertSame('https://example.com/api/max/webhook', $decoded['url'] ?? null);
        self::assertSame(['bot_started', 'message_created'], $decoded['update_types'] ?? null);
        self::assertSame('secret', $decoded['secret'] ?? null);

        self::assertStringContainsString('test-token', (string) json_encode($captured['options'], JSON_UNESCAPED_SLASHES));
    }

    public function testListReturnsSubscriptions(): void
    {
        $httpClient = new MockHttpClient(
            new MockResponse(json_encode(['subscriptions' => []], JSON_THROW_ON_ERROR), ['http_code' => 200]),
        );

        $client = new MaxWebhookClient($httpClient, 'https://platform-api2.max.ru', 'test-token');

        self::assertSame(['subscriptions' => []], $client->list());
    }

    public function testSubscribeReturnsFailureWhenNotConfigured(): void
    {
        $client = new MaxWebhookClient(new MockHttpClient(), '', '');

        $result = $client->subscribe('https://example.com/api/max/webhook', ['bot_started'], 'secret');

        self::assertFalse($result['success'] ?? null);
        $message = $result['message'] ?? null;
        self::assertIsString($message);
        self::assertStringContainsString('not configured', $message);
    }
}
