<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity]
#[ORM\Table(name: 'treatmentCodeGenerator')]
class TreatmentCodeGenerator
{
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $code = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $generate = null;

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
