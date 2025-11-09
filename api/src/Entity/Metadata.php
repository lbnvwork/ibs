<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity]
#[ORM\Table(name: 'metadata')]
class Metadata
{
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $version = null;

}
