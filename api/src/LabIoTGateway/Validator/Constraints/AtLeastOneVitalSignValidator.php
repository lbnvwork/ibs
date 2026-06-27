<?php

declare(strict_types=1);

namespace App\LabIoTGateway\Validator\Constraints;

use App\LabIoTGateway\Entity\PatientVitals;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class AtLeastOneVitalSignValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof AtLeastOneVitalSign) {
            throw new UnexpectedTypeException($constraint, AtLeastOneVitalSign::class);
        }

        if (!$value instanceof PatientVitals) {
            throw new UnexpectedTypeException($value, PatientVitals::class);
        }

        if ($value->getHb() === null &&
            $value->getHeartRate() === null &&
            $value->getSystolicPressure() === null &&
            $value->getDiastolicPressure() === null &&
            $value->getSaturation() === null
        ) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}