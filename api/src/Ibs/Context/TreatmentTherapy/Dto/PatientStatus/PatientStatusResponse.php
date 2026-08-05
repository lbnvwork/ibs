<?php

declare(strict_types=1);

namespace App\DTO\PatientStatus;

class PatientStatusResponse
{
    public function __construct(
        public int $id,
        public string $status
    ) {}
}