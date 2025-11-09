<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity]
#[ORM\Table(name: 'treatmentCodeGenerator')]
class TreatmentCodeGenerator
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private ?int $code = null;

    #[ORM\Column(type: 'integer')]
    private ?int $generate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?int
    {
        return $this->code;
    }

    public function setCode(?int $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getGenerate(): ?int
    {
        return $this->generate;
    }

    public function setGenerate(?int $generate): self
    {
        $this->generate = $generate;
        return $this;
    }
}
