<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\Communication\Command;

use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\Communication\Command\CollectMaxContactsCommand;
use Ibs\Context\Communication\Entity\PatientChannelIdentity;
use Ibs\Context\Communication\Repository\PatientChannelIdentityRepository;
use Ibs\Context\Communication\Service\MaxUpdatePoller;
use Ibs\Context\Communication\Service\MaxUpdateProcessor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class CollectMaxContactsCommandTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/max_cmd_' . uniqid('', true);
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            if (!$item instanceof \SplFileInfo) {
                continue;
            }

            $path = $item->getPathname();
            if ($item->isDir()) {
                rmdir($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }

    /**
     * @param array<int, array<string, mixed>> $updates
     */
    private function tester(
        array $updates,
        ?string $marker,
        PatientChannelIdentityRepository $identities,
        EntityManagerInterface $entityManager,
    ): CommandTester {
        $httpClient = new MockHttpClient(
            new MockResponse(
                json_encode(['updates' => $updates, 'marker' => $marker], JSON_THROW_ON_ERROR),
                ['http_code' => 200],
            ),
        );

        $poller = new MaxUpdatePoller($httpClient, 'https://platform-api2.max.ru', 'test-token');
        $processor = new MaxUpdateProcessor($identities, $entityManager);
        $command = new CollectMaxContactsCommand($poller, $processor, $this->tempDir);

        return new CommandTester($command);
    }

    public function testSavesContactFromBotStartedUpdate(): void
    {
        $identities = $this->createStub(PatientChannelIdentityRepository::class);
        $identities->method('findOneByPatientAndChannel')->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('persist')
            ->with(self::callback(static function ($entity): bool {
                if (!$entity instanceof PatientChannelIdentity) {
                    return false;
                }

                return $entity->getPatientId() === 999011
                    && $entity->getChannelType() === 'max'
                    && $entity->getValue() === 'chat-42';
            }));
        $entityManager->expects(self::once())->method('flush');

        $tester = $this->tester(
            [['update_type' => 'bot_started', 'chat_id' => 'chat-42', 'payload' => '999011']],
            'marker-1',
            $identities,
            $entityManager,
        );

        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Сохранён контакт: patient_id=999011, chat_id=chat-42', $tester->getDisplay());
        self::assertStringContainsString('сохранено контактов: 1', $tester->getDisplay());
        self::assertSame('marker-1', trim((string) file_get_contents($this->tempDir . '/var/max_updates_marker')));
    }

    public function testSkipsUpdateWithoutNumericPayload(): void
    {
        $identities = $this->createStub(PatientChannelIdentityRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('persist');

        $tester = $this->tester(
            [
                ['update_type' => 'bot_started', 'chat_id' => 'chat-42', 'payload' => 'not-a-number'],
                ['update_type' => 'message_created', 'chat_id' => 'chat-43'],
            ],
            'marker-2',
            $identities,
            $entityManager,
        );

        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('сохранено контактов: 0', $tester->getDisplay());
    }

    public function testUpdatesExistingContact(): void
    {
        $existing = (new PatientChannelIdentity())
            ->setPatientId(999011)
            ->setChannelType('max')
            ->setValue('old-chat');

        $identities = $this->createStub(PatientChannelIdentityRepository::class);
        $identities->method('findOneByPatientAndChannel')->willReturn($existing);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('persist');
        $entityManager->expects(self::once())->method('flush');

        $tester = $this->tester(
            [['update_type' => 'bot_started', 'chat_id' => 'chat-42', 'payload' => '999011']],
            'marker-3',
            $identities,
            $entityManager,
        );

        $tester->execute([]);

        self::assertSame('chat-42', $existing->getValue());
    }

    public function testReturnsFailureWhenMaxUnavailable(): void
    {
        $poller = new MaxUpdatePoller(new MockHttpClient(), '', '');
        $identities = $this->createStub(PatientChannelIdentityRepository::class);
        $entityManager = $this->createStub(EntityManagerInterface::class);

        $processor = new MaxUpdateProcessor($identities, $entityManager);
        $command = new CollectMaxContactsCommand($poller, $processor, $this->tempDir);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('MAX не настроен', $tester->getDisplay());
    }

    public function testTimeoutOptionIsPassedToPoller(): void
    {
        $capturedUrl = null;
        $httpClient = new MockHttpClient(
            static function (string $method, string $url, array $options = []) use (&$capturedUrl): MockResponse {
                $capturedUrl = $url;

                return new MockResponse(
                    json_encode(['updates' => [], 'marker' => null], JSON_THROW_ON_ERROR),
                    ['http_code' => 200],
                );
            },
        );

        $identities = $this->createStub(PatientChannelIdentityRepository::class);
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $poller = new MaxUpdatePoller($httpClient, 'https://platform-api2.max.ru', 'test-token');
        $processor = new MaxUpdateProcessor($identities, $entityManager);
        $command = new CollectMaxContactsCommand($poller, $processor, $this->tempDir);
        $tester = new CommandTester($command);

        $tester->execute(['--timeout' => '42']);

        self::assertNotNull($capturedUrl);
        parse_str((string) parse_url($capturedUrl, PHP_URL_QUERY), $query);
        self::assertSame('42', $query['timeout'] ?? null);
    }

    public function testLoopWithMaxIterationsPollsOnce(): void
    {
        $calls = 0;
        $httpClient = new MockHttpClient(
            static function (string $method, string $url, array $options = []) use (&$calls): MockResponse {
                $calls++;

                return new MockResponse(
                    json_encode(['updates' => [], 'marker' => null], JSON_THROW_ON_ERROR),
                    ['http_code' => 200],
                );
            },
        );

        $identities = $this->createStub(PatientChannelIdentityRepository::class);
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $poller = new MaxUpdatePoller($httpClient, 'https://platform-api2.max.ru', 'test-token');
        $processor = new MaxUpdateProcessor($identities, $entityManager);
        $command = new CollectMaxContactsCommand($poller, $processor, $this->tempDir);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['--loop' => true, '--max-iterations' => '1']);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertSame(1, $calls);
    }

    public function testInvalidTimeoutReturnsInvalid(): void
    {
        $identities = $this->createStub(PatientChannelIdentityRepository::class);
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $poller = new MaxUpdatePoller(new MockHttpClient(), 'https://platform-api2.max.ru', 'test-token');
        $processor = new MaxUpdateProcessor($identities, $entityManager);
        $command = new CollectMaxContactsCommand($poller, $processor, $this->tempDir);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['--timeout' => 'abc']);

        self::assertSame(Command::INVALID, $exitCode);
        self::assertStringContainsString('timeout', $tester->getDisplay());
    }
}
