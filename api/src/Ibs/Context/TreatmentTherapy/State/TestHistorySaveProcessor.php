<?php

declare(strict_types=1);

namespace Ibs\Context\TreatmentTherapy\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Ibs\Context\Communication\Model\NotificationMessage;
use Ibs\Context\Communication\Model\Priority;
use Ibs\Context\Communication\Model\Recipient;
use Ibs\Context\Communication\Service\NotificationService;
use Ibs\Context\TreatmentTherapy\Entity\TestHistory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/** @implements ProcessorInterface<TestHistory, TestHistory> */
class TestHistorySaveProcessor implements ProcessorInterface
{
    private const NOTIFICATION_CHANNEL = 'max';

    /** @param ProcessorInterface<TestHistory, TestHistory> $persistProcessor */
    public function __construct(
        #[Autowire('@api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private NotificationService $notificationService,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): TestHistory
    {
        /** @var TestHistory $data */
        $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        $this->sendPatientMessage($result);

        return $result;
    }

    /**
     * Отправляет пациенту итоговый текст «Сообщения пациенту» (`TestHistory.comment`).
     * Текст готовится на фронте по превью шаблона (резолв — отдельно, не при отправке).
     * Отправка — асинхронная (приоритет ROUTINE) и не должна блокировать сохранение анализа:
     * если лечения/пациента/текста нет — пропускаем; если доставка упала — только логируем.
     */
    private function sendPatientMessage(TestHistory $testHistory): void
    {
        $treatment = $testHistory->getTreatment();
        if (null === $treatment) {
            return;
        }

        $patientId = $treatment->getPatient()?->getId();
        if (null === $patientId) {
            return;
        }

        $comment = $testHistory->getComment();
        if (null === $comment || '' === trim($comment)) {
            return;
        }

        try {
            $this->notificationService->send(
                new Recipient(patientId: $patientId, treatmentId: $treatment->getId()),
                new NotificationMessage(body: $comment),
                [self::NOTIFICATION_CHANNEL],
                Priority::ROUTINE,
            );
        } catch (\Throwable $exception) {
            error_log(sprintf(
                'Не удалось отправить MAX-уведомление о результате анализа (treatment %d): %s',
                $treatment->getId() ?? 0,
                $exception->getMessage(),
            ));
        }
    }
}
