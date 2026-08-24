<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\Communication\Service;

use Ibs\Context\Communication\Service\MaxUpdatePoller;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MaxUpdatePollerTest extends TestCase
{
    public function testFetchReturnsUpdatesAndMarker(): void
    {
        $httpClient = new MockHttpClient(
            new MockResponse(
                json_encode([
                    'updates' => [
                        ['update_type' => 'bot_started', 'chat_id' => 'chat-42', 'payload' => '999011'],
                    ],
                    'marker' => 'marker-1',
                ], JSON_THROW_ON_ERROR),
                ['http_code' => 200],
            ),
        );

        $poller = new MaxUpdatePoller($httpClient, 'https://platform-api2.max.ru', 'test-token');
        $result = $poller->fetch(null, ['bot_started']);

        self::assertSame('marker-1', $result['marker']);
        $updates = $result['updates'];
        self::assertCount(1, $updates);
        $update = $updates[0];
        self::assertIsArray($update);
        self::assertSame('chat-42', $update['chat_id']);
    }

    public function testFetchSendsExpectedRequest(): void
    {
        $captured = null;
        $httpClient = new MockHttpClient(
            static function (string $method, string $url, array $options = []) use (&$captured): MockResponse {
                $captured = ['method' => $method, 'url' => $url, 'options' => $options];

                return new MockResponse(
                    json_encode(['updates' => [], 'marker' => null], JSON_THROW_ON_ERROR),
                    ['http_code' => 200],
                );
            },
        );

        $poller = new MaxUpdatePoller($httpClient, 'https://platform-api2.max.ru', 'test-token');
        $poller->fetch('prev-marker', ['bot_started'], limit: 100, timeout: 1);

        self::assertNotNull($captured);
        self::assertSame('GET', $captured['method']);
        self::assertSame('/updates', parse_url($captured['url'], PHP_URL_PATH));

        parse_str((string) parse_url($captured['url'], PHP_URL_QUERY), $query);
        self::assertSame('bot_started', $query['types'] ?? null);
        self::assertSame('prev-marker', $query['marker'] ?? null);

        $optionsJson = (string) json_encode($captured['options'], JSON_UNESCAPED_SLASHES);
        self::assertStringContainsString('test-token', $optionsJson);
        self::assertStringNotContainsString('Bearer', $optionsJson);
    }

    public function testFetchReturnsEmptyWhenUnavailable(): void
    {
        $poller = new MaxUpdatePoller(new MockHttpClient(), '', '');

        $result = $poller->fetch();

        self::assertSame([], $result['updates']);
        self::assertNull($result['marker']);
    }

    public function testFetchReturnsEmptyOnTransportException(): void
    {
        $exception = new class('timeout') extends \RuntimeException implements TransportExceptionInterface {
        };

        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willThrowException($exception);

        $poller = new MaxUpdatePoller($httpClient, 'https://platform-api2.max.ru', 'test-token');
        $result = $poller->fetch();

        self::assertSame([], $result['updates']);
        self::assertNull($result['marker']);
    }

    public function testIsAvailable(): void
    {
        $httpClient = new MockHttpClient();

        self::assertFalse((new MaxUpdatePoller($httpClient, '', 'token'))->isAvailable());
        self::assertFalse((new MaxUpdatePoller($httpClient, 'https://platform-api2.max.ru', ''))->isAvailable());
        self::assertTrue((new MaxUpdatePoller($httpClient, 'https://platform-api2.max.ru', 'token'))->isAvailable());
    }
}
