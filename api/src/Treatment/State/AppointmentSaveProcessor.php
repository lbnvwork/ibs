<?php

declare(strict_types=1);

namespace App\Treatment\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Appointment;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class AppointmentSaveProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire('@api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Appointment
    {
        // Автоматически заполняем врача из текущего пользователя
        $user = $this->security->getUser();
        if ($user) {
            $data->setDoctorName($user->getUserName() ?? $user->getUserIdentifier());
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}