<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Ibs\Context\Communication\Entity\PatientChannelIdentity;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Оборачивает persist PatientChannelIdentity и маппит нарушение уникальности
 * (patient_id, channel_type) в HTTP 409 Conflict вместо 500.
 */
final class PatientChannelIdentityProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire('@api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): PatientChannelIdentity
    {
        try {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        } catch (UniqueConstraintViolationException) {
            throw new ConflictHttpException('Контакт для данного пациента и канала уже существует.');
        }
    }
}
