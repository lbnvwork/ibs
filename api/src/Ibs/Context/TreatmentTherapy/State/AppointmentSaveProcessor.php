<?php

declare(strict_types=1);

namespace Ibs\Context\TreatmentTherapy\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Ibs\Context\Communication\Model\NotificationMessage;
use Ibs\Context\Communication\Model\Priority;
use Ibs\Context\Communication\Model\Recipient;
use Ibs\Context\Communication\Service\NotificationService;
use Ibs\Context\TreatmentTherapy\Entity\Appointment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/** @implements ProcessorInterface<Appointment, Appointment> */
class AppointmentSaveProcessor implements ProcessorInterface
{
    private EntityManagerInterface $entityManager;

    /** @param ProcessorInterface<Appointment, Appointment> $persistProcessor */
    public function __construct(
        #[Autowire('@api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security,
        private NotificationService $notificationService,
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Appointment
    {
        /** @var Appointment $data */
        $treatment = $data->getTreatment();

        // Проверка активности лечения
        if ($treatment === null || $treatment->getRealEndDt() !== null) {
            throw new UnprocessableEntityHttpException('Лечение не активно. Сохранение назначения невозможно.');
        }

        // Автоматическое заполнение дат
        $now = new \DateTime();
        if ($data->getCreationDt() === null) {
            $data->setCreationDt($now);
        }
        $data->setModDt($now);

        // Проверка 50% изменения дозы
        $lastAppointment = $this->entityManager->getRepository(Appointment::class)
            ->findOneBy(
                ['treatment' => $treatment],
                ['appointmentDt' => 'DESC']
            );
        if ($lastAppointment) {
            $lastDose = $lastAppointment->getDoze();
            $newDose = $data->getDoze();
            if ($lastDose > 0) {
                $change = abs($newDose - $lastDose) / $lastDose;
                if ($change > 0.5) {
                    error_log(sprintf(
                        'Предупреждение: изменение дозы более 50%% для лечения %d, старая доза %.2f, новая доза %.2f',
                        $treatment->getId(),
                        $lastDose,
                        $newDose
                    ));
                }
            }
        }

        // Автоматическое заполнение врача
        $user = $this->security->getUser();
        if ($user) {
            $data->setDoctorName($user->getUserIdentifier());
        }

        $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        $this->sendPatientMessage($data);

        return $result;
    }

    /**
     * Отправляет итоговый текст «Сообщение пациенту» (comment) в MAX после успешного
     * сохранения назначения. Сбой доставки или отсутствие лечения/пациента/текста
     * не блокирует сохранение.
     */
    private function sendPatientMessage(Appointment $appointment): void
    {
        $treatment = $appointment->getTreatment();
        $patient = $treatment?->getPatient();
        $comment = $appointment->getComment();

        if ($treatment === null || $patient === null || $comment === null || trim($comment) === '') {
            return;
        }

        try {
            $this->notificationService->send(
                new Recipient(patientId: $patient->getId(), treatmentId: $treatment->getId()),
                new NotificationMessage(body: $comment),
                ['max'],
                Priority::ROUTINE,
            );
        } catch (\Throwable $exception) {
            // Сбой доставки не должен блокировать сохранение назначения, но ошибку логируем.
            error_log(sprintf(
                'Не удалось отправить MAX-уведомление (treatment_id=%s, patient_id=%s): %s',
                (string) ($treatment->getId() ?? 'null'),
                (string) ($patient->getId() ?? 'null'),
                $exception->getMessage(),
            ));
        }
    }
}
