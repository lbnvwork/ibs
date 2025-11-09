<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity]
#[ORM\Table(name: 'treatment_dode_generators')]
class TreatmentCodeGenerator
{
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $code = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $generate = null;

}
