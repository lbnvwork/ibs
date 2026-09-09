<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\TreatmentTherapy\State;

use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Tests\Ibs\Context\Communication\Service\FakeChannel;
use App\Tests\Ibs\Context\Communication\Service\ImmediateRetrySleeper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Ibs\Context\Communication\Entity\PatientChannelIdentity;
use Ibs\Context\Communication\Repository\NotificationLogRepository;
use Ibs\Context\Communication\Repository\NotificationTemplateRepository;
use Ibs\Context\Communication\Repository\PatientChannelIdentityRepository;
use Ibs\Context\Communication\Service\ChannelRegistry;
use Ibs\Context\Communication\Service\NotificationService;
use Ibs\Context\Communication\Service\PatientContactResolver;
use Ibs\Context\Communication\Service\TemplateResolver;
use Ibs\Context\PatientManagement\Entity\Patient;
use Ibs\Context\TreatmentTherapy\Entity\Appointment;
use Ibs\Context\TreatmentTherapy\Entity\Treatment;
use Ibs\Context\TreatmentTherapy\State\AppointmentSaveProcessor;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class AppointmentSaveProcessorTest extends TestCase
{
    public function testPersistsAndSendsCommentOnCreate(): void
    {
        $max = FakeChannel::succeeding('max');
        $processor = $this->newProcessor($this->newNotificationService($max));

        $appointment = $this->createAppointment(
            'Ваше МНО - 2.3. С 01.08.2026 ВАМ НУЖНО ПРИНИМАТЬ 2.5 варфарина.',
            $this->createTreatment($this->createPatient()),
        );

        $result = $processor->process($appointment, new Post(), [], []);

        self::assertSame($appointment, $result);
        self::assertCount(1, $max->calls);
        self::assertSame('Ваше МНО - 2.3. С 01.08.2026 ВАМ НУЖНО ПРИНИМАТЬ 2.5 варфарина.', $max->calls[0]['message']->body);
        self::assertSame(1, $max->calls[0]['recipient']->patientId);
        self::assertSame(1, $max->calls[0]['recipient']->treatmentId);
    }

    public function testSkipsSendWithoutPatient(): void
    {
        $max = FakeChannel::succeeding('max');
        $processor = $this->newProcessor($this->newNotificationService($max));

        $appointment = $this->createAppointment('Текст сообщения', $this->createTreatment(null));

        $processor->process($appointment, new Post(), [], []);

        self::assertCount(0, $max->calls);
    }

    public function testSkipsSendWithoutComment(): void
    {
        $max = FakeChannel::succeeding('max');
        $processor = $this->newProcessor($this->newNotificationService($max));

        $appointment = $this->createAppointment('', $this->createTreatment($this->createPatient()));

        $processor->process($appointment, new Post(), [], []);

        self::assertCount(0, $max->calls);
    }

    public function testSendFailureDoesNotBlockPersist(): void
    {
        $max = FakeChannel::failing('max', 'Delivery failed');
        $processor = $this->newProcessor($this->newNotificationService($max));

        $appointment = $this->createAppointment('Текст сообщения', $this->createTreatment($this->createPatient()));

        $result = $processor->process($appointment, new Post(), [], []);

        self::assertSame($appointment, $result);
    }

    private function newProcessor(NotificationService $notificationService): AppointmentSaveProcessor
    {
        $persistProcessor = $this->createStub(ProcessorInterface::class);
        $persistProcessor->method('process')->willReturnArgument(0);

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $repository = $this->createStub(EntityRepository::class);
        $repository->method('findOneBy')->willReturn(null);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($repository);

        return new AppointmentSaveProcessor($persistProcessor, $security, $notificationService, $entityManager);
    }

    private function newNotificationService(FakeChannel $channel): NotificationService
    {
        $templateRepository = $this->createStub(NotificationTemplateRepository::class);
        $templateRepository->method('findOneByCodeAndChannel')->willReturn(null);
        $templateResolver = new TemplateResolver($templateRepository);

        $identityRepository = $this->createStub(PatientChannelIdentityRepository::class);
        $identityRepository->method('findOneByPatientAndChannel')
            ->willReturnCallback(static function (int $patientId, string $channelType): PatientChannelIdentity {
                return (new PatientChannelIdentity())
                    ->setPatientId($patientId)
                    ->setChannelType($channelType)
                    ->setValue('chat-123');
            });
        $contactResolver = new PatientContactResolver($identityRepository);

        $logRepository = $this->createStub(NotificationLogRepository::class);

        return new NotificationService(
            new ChannelRegistry([$channel]),
            $templateResolver,
            $contactResolver,
            $logRepository,
            null,
            new ImmediateRetrySleeper(),
        );
    }

    private function createAppointment(string $comment, Treatment $treatment): Appointment
    {
        $appointment = new Appointment();
        $appointment->setAppointmentDt(new \DateTime('2026-08-01 10:00:00'));
        $appointment->setDoze(2.5);
        $appointment->setDoctorName('doctor');
        $appointment->setTreatment($treatment);
        $appointment->setComment($comment);

        return $appointment;
    }

    private function createTreatment(?Patient $patient): Treatment
    {
        $treatment = new Treatment();
        $treatment->setPatient($patient);
        $this->setId($treatment, 1);

        return $treatment;
    }

    private function createPatient(): Patient
    {
        $patient = new Patient();
        $this->setId($patient, 1);

        return $patient;
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionProperty($entity, 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($entity, $id);
    }
}
