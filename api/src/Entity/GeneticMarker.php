<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;

#[ApiResource(
    operations: [
        new Get()
    ]
)]
#[ORM\Entity]
#[ORM\Table(name: 'genetic_markers')]
class GeneticMarker
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 30)]
    private string $geneSymbol;

    #[ORM\Column(type: 'string', length: 150)]
    private string $fullName;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $rsId = null;

    #[ORM\OneToMany(targetEntity: GeneticMarkerValue::class, mappedBy: 'marker', cascade: ['persist'])]
    private Collection $possibleValues;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    public function __construct()
    {
        $this->possibleValues = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGeneSymbol(): string
    {
        return $this->geneSymbol;
    }

    public function setGeneSymbol(string $geneSymbol): self
    {
        $this->geneSymbol = $geneSymbol;
        return $this;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): self
    {
        $this->fullName = $fullName;
        return $this;
    }

    public function getRsId(): ?string
    {
        return $this->rsId;
    }

    public function setRsId(?string $rsId): self
    {
        $this->rsId = $rsId;
        return $this;
    }

    public function getPossibleValues(): Collection
    {
        return $this->possibleValues;
    }

    public function addPossibleValue(GeneticMarkerValue $value): self
    {
        if (!$this->possibleValues->contains($value)) {
            $this->possibleValues->add($value);
            $value->setMarker($this);
        }
        return $this;
    }

    public function removePossibleValue(GeneticMarkerValue $value): self
    {
        if ($this->possibleValues->contains($value)) {
            $this->possibleValues->removeElement($value);
            if ($value->getMarker() === $this) {
                $value->setMarker(null);
            }
        }
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }
}