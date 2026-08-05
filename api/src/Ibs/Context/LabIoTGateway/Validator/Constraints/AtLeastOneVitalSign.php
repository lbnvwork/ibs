<?php

declare(strict_types=1);

namespace App\LabIoTGateway\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Attribute;

#[Attribute]
class AtLeastOneVitalSign extends Constraint
{
    public string $message = 'Необходимо указать хотя бы один витальный показатель.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}