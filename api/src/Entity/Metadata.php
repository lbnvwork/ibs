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

    public function getversion(): ?int
    {
        return $this->version;
    }

    public function setversion(?int $version): self
    {
        $this->version = $version;
        return $this;
    }

}
