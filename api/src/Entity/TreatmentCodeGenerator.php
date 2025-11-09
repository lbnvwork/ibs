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

    public function getcode(): ?int
    {
        return $this->code;
    }

    public function setcode(?int $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getgenerate(): ?int
    {
        return $this->generate;
    }

    public function setgenerate(?int $generate): self
    {
        $this->generate = $generate;
        return $this;
    }

}
