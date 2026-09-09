<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\TreatmentTherapy\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Tests\Ibs\Context\Communication\Service\FakeChannel;
use App\Tests\Ibs\Context\Communication\Service\ImmediateRetrySleeper;
use Ibs\Context\Communication\Entity\NotificationLog;
use Ibs\Context\Communication\Entity\PatientChannelIdentity;
use Ibs\Context\Communication\Repository\NotificationLogRepository;
use Ibs\Context\Communication\Repository\NotificationTemplateRepository;
use Ibs\Context\Communication\Repository\PatientChannelIdentityRepository;
use Ibs\Context\Communication\Service\ChannelRegistry;
use Ibs\Context\Communication\Service\NotificationService;
use Ibs\Context\Communication\Service\PatientContactResolver;
use Ibs\Context\Communication\Service\TemplateResolver;
use Ibs\Context\PatientManagement\Entity\Patient;
use Ibs\Context\TreatmentTherapy\Entity\TestHistory;
use Ibs\Context\TreatmentTherapy\Entity\Treatment;
use Ibs\Context\TreatmentTherapy\State\TestHistorySaveProcessor;
use PHPUnit\Framework\TestCase;

class TestHistorySaveProcessorTest extends TestCase
{
    private Operation $operation;

    /** @var NotificationLog[] */
    private array $savedLogs = [];

    protected function setUp(): void
    {
        $this->operation = new Post();
        $this->savedLogs = [];
    }

    public function testPersistsAndSendsCommentOnCreate(): void
    {
        $testHistory = new TestHistory();
        $testHistory->setMno(2.3);
        $testHistory->setComment('Ваше МНО - 2.3. Дозировку варфарина оставьте прежней');
        $testHistory->setTreatment($this->treatment($this->patient(42), 10));

        $channel = FakeChannel::succeeding('max');
        $service = $this->newService($channel);

        $processor = new TestHistorySaveProcessor($this->persistProcessor($testHistory), $service);

        $result = $processor->process($testHistory, $this->operation, [], []);

        $this->assertSame($testHistory, $result);
        $this->assertCount(1, $channel->calls);

        $message = $channel->calls[0]['message'];
        $this->assertSame('Ваше МНО - 2.3. Дозировку варфарина оставьте прежней', $message->body);
        $this->assertNull($message->template);
        $this->assertSame([], $message->data);

        $this->assertCount(1, $this->savedLogs);
        $this->assertSame('max', $this->savedLogs[0]->getChannelType());
        $this->assertSame('routine', $this->savedLogs[0]->getPriority());
        $this->assertSame(42, $this->savedLogs[0]->getPatientId());
        $this->assertSame(10, $this->savedLogs[0]->getTreatmentId());
    }

    public function testSkipsSendWithoutTreatment(): void
    {
        $testHistory = new TestHistory();
        $testHistory->setComment('Ваше МНО - 2.3. Дозировку варфарина оставьте прежней');

        $channel = FakeChannel::succeeding('max');
        $service = $this->newService($channel);

        $processor = new TestHistorySaveProcessor($this->persistProcessor($testHistory), $service);

        $this->assertSame($testHistory, $processor->process($testHistory, $this->operation, [], []));
        $this->assertCount(0, $channel->calls);
        $this->assertCount(0, $this->savedLogs);
    }

    public function testSkipsSendWithoutPatient(): void
    {
        $testHistory = new TestHistory();
        $testHistory->setComment('Ваше МНО - 2.3. Дозировку варфарина оставьте прежней');
        $testHistory->setTreatment($this->treatment(null, 10));

        $channel = FakeChannel::succeeding('max');
        $service = $this->newService($channel);

        $processor = new TestHistorySaveProcessor($this->persistProcessor($testHistory), $service);

        $this->assertSame($testHistory, $processor->process($testHistory, $this->operation, [], []));
        $this->assertCount(0, $channel->calls);
        $this->assertCount(0, $this->savedLogs);
    }

    public function testSkipsSendWithoutComment(): void
    {
        $testHistory = new TestHistory();
        $testHistory->setMno(2.3);
        $testHistory->setTreatment($this->treatment($this->patient(42), 10));

        $channel = FakeChannel::succeeding('max');
        $service = $this->newService($channel);

        $processor = new TestHistorySaveProcessor($this->persistProcessor($testHistory), $service);

        $this->assertSame($testHistory, $processor->process($testHistory, $this->operation, [], []));
        $this->assertCount(0, $channel->calls);
        $this->assertCount(0, $this->savedLogs);
    }

    public function testSendFailureDoesNotBlockPersist(): void
    {
        $testHistory = new TestHistory();
        $testHistory->setMno(2.3);
        $testHistory->setComment('Ваше МНО - 2.3. Дозировку варфарина оставьте прежней');
        $testHistory->setTreatment($this->treatment($this->patient(42), 10));

        $channel = FakeChannel::failing('max', 'delivery failed');
        $service = $this->newService($channel);

        $processor = new TestHistorySaveProcessor($this->persistProcessor($testHistory), $service);

        $this->assertSame($testHistory, $processor->process($testHistory, $this->operation, [], []));

        $this->assertCount(1, $channel->calls);
        $this->assertCount(1, $this->savedLogs);
        $this->assertSame('failed', $this->savedLogs[0]->getStatus());
    }

    /** @return ProcessorInterface<TestHistory, TestHistory> */
    private function persistProcessor(TestHistory $result): ProcessorInterface
    {
        $persist = $this->createMock(ProcessorInterface::class);
        $persist->expects(self::once())
            ->method('process')
            ->willReturn($result);

        return $persist;
    }

    private function newService(FakeChannel $channel): NotificationService
    {
        // Шаблон не участвует в отправке (уходит итоговый comment) — резолвер не вызывается.
        $templateRepository = $this->createStub(NotificationTemplateRepository::class);

        $identityRepository = $this->createStub(PatientChannelIdentityRepository::class);
        $identityRepository->method('findOneByPatientAndChannel')
            ->willReturnCallback(function (int $patientId, string $channelType): PatientChannelIdentity {
                return (new PatientChannelIdentity())
                    ->setPatientId($patientId)
                    ->setChannelType($channelType)
                    ->setValue('chat-' . $patientId);
            });

        $logRepository = $this->createStub(NotificationLogRepository::class);
        $logRepository->method('save')
            ->willReturnCallback(function (NotificationLog $log): void {
                $this->savedLogs[] = $log;
            });
        $logRepository->method('flush')->willReturnCallback(function (): void {});

        return new NotificationService(
            new ChannelRegistry([$channel]),
            new TemplateResolver($templateRepository),
            new PatientContactResolver($identityRepository),
            $logRepository,
            null,
            new ImmediateRetrySleeper(),
        );
    }

    private function patient(int $id): Patient
    {
        $patient = $this->createStub(Patient::class);
        $patient->method('getId')->willReturn($id);

        return $patient;
    }

    private function treatment(?Patient $patient, int $id): Treatment
    {
        $treatment = $this->createStub(Treatment::class);
        $treatment->method('getId')->willReturn($id);
        $treatment->method('getPatient')->willReturn($patient);

        return $treatment;
    }
}
