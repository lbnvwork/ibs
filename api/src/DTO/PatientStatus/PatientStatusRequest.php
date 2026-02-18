<?php

declare(strict_types=1);

namespace App\DTO\PatientStatus;

use Symfony\Component\Validator\Constraints as Assert;

class PatientStatusRequest
{
    /**
     * @var int[]
     */
    #[Assert\NotBlank]
    #[Assert\All([
        new Assert\Type('integer'),
        new Assert\Positive()
    ])]
    public array $ids = [];
}