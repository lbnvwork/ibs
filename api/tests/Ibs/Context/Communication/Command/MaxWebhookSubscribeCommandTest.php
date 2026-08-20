<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\Communication\Command;

use Ibs\Context\Communication\Command\MaxWebhookSubscribeCommand;
use Ibs\Context\Communication\Service\MaxWebhookClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class MaxWebhookSubscribeCommandTest extends TestCase
{
    public function testSubscribesAndReportsSuccess(): void
    {
        $httpClient = new MockHttpClient(
            new MockResponse(json_encode(['success' => true], JSON_THROW_ON_ERROR), ['http_code' => 200]),
        );

        $client = new MaxWebhookClient($httpClient, 'https://platform-api2.max.ru', 'test-token');
        $command = new MaxWebhookSubscribeCommand($client, 'top-secret');
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['url' => 'https://example.com/api/max/webhook']);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('success', $tester->getDisplay());
    }

    public function testSubscribesWithCustomUpdateTypes(): void
    {
        $captured = null;
        $httpClient = new MockHttpClient(
            static function (string $method, string $url, array $options = []) use (&$captured): MockResponse {
                $captured = ['url' => $url, 'options' => $options];

                return new MockResponse(json_encode(['success' => true], JSON_THROW_ON_ERROR), ['http_code' => 200]);
            },
        );

        $client = new MaxWebhookClient($httpClient, 'https://platform-api2.max.ru', 'test-token');
        $command = new MaxWebhookSubscribeCommand($client, 'top-secret');
        $tester = new CommandTester($command);

        $tester->execute(['url' => 'https://example.com/api/max/webhook', '--update-types' => 'bot_started']);

        self::assertNotNull($captured);
        $body = $captured['options']['body'] ?? null;
        self::assertIsString($body);
        $decoded = json_decode($body, true);
        self::assertIsArray($decoded);
        self::assertSame(['bot_started'], $decoded['update_types'] ?? null);
    }

    public function testEmptyUrlReturnsInvalid(): void
    {
        $client = new MaxWebhookClient(new MockHttpClient(), 'https://platform-api2.max.ru', 'test-token');
        $command = new MaxWebhookSubscribeCommand($client, 'top-secret');
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['url' => '']);

        self::assertSame(Command::INVALID, $exitCode);
    }
}
