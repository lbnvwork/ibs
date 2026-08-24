<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Model;

/**
 * Получатель уведомления. Содержит только идентификаторы пациента и лечения;
 * адрес для конкретного канала резолвится через PatientContactResolver.
 */
final class Recipient
{
    public function __construct(
        public readonly ?int $patientId = null,
        public readonly ?int $treatmentId = null,
    ) {
    }
}
