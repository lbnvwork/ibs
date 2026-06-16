<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'marker_drug_relations')]
#[ORM\UniqueConstraint(name: 'uniq_marker_drug', columns: ['marker_id', 'drug_id'])]
class MarkerDrugRelation
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: GeneticMarker::class)]
    #[ORM\JoinColumn(name: 'marker_id', referencedColumnName: 'id', nullable: false)]
    private ?GeneticMarker $marker = null;

    #[ORM\ManyToOne(targetEntity: Drug::class)]
    #[ORM\JoinColumn(name: 'drug_id', referencedColumnName: 'id', nullable: false)]
    private ?Drug $drug = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMarker(): ?GeneticMarker
    {
        return $this->marker;
    }

    public function setMarker(?GeneticMarker $marker): self
    {
        $this->marker = $marker;
        return $this;
    }

    public function getDrug(): ?Drug
    {
        return $this->drug;
    }

    public function setDrug(?Drug $drug): self
    {
        $this->drug = $drug;
        return $this;
    }
}