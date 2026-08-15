<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Service;

use Ibs\Context\Communication\Repository\PatientChannelIdentityRepository;

/**
 * Возвращает адрес/идентификатор получателя для конкретного канала,
 * хранящийся в отдельной сущности PatientChannelIdentity.
 */
final class PatientContactResolver
{
    public function __construct(
        private readonly PatientChannelIdentityRepository $identities,
    ) {
    }

    public function get(?int $patientId, string $channelType): ?string
    {
        if (null === $patientId || $patientId < 1) {
            return null;
        }

        $identity = $this->identities->findOneByPatientAndChannel($patientId, $channelType);

        return $identity?->getValue();
    }
}