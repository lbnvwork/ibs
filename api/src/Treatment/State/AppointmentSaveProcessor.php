<?php

declare(strict_types=1);

namespace App\Treatment\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Appointment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class AppointmentSaveProcessor implements ProcessorInterface
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        #[Autowire('@api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security,
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

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}