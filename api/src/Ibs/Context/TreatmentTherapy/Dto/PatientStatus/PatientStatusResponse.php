<?php

declare(strict_types=1);

namespace Ibs\Context\TreatmentTherapy\Dto\PatientStatus;

class PatientStatusResponse
{
    public function __construct(
        public int $id,
        public string $status
    ) {}
}