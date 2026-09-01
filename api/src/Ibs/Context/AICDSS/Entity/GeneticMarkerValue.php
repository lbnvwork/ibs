<?php

declare(strict_types=1);

namespace Ibs\Context\AICDSS\Entity;

use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;

#[ApiResource(
    operations: [
        new Get()
    ]
)]
#[ORM\Entity]
#[ORM\Table(name: 'genetic_marker_values', options: ['comment' => 'Значения генетических маркеров'])]
class GeneticMarkerValue
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', options: ['comment' => 'Идентификатор'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: GeneticMarker::class, inversedBy: 'possibleValues')]
    #[ORM\JoinColumn(name: 'marker_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Генетический маркер'])]
    private ?GeneticMarker $marker = null;

    #[ORM\Column(type: 'string', length: 50, options: ['comment' => 'Генотип'])]
    private string $value;

    #[ORM\Column(type: 'string', length: 255, options: ['comment' => 'Метка'])]
    private string $label;

    #[ORM\Column(type: 'text', nullable: true, options: ['comment' => 'Описание'])]
    private ?string $description = null;

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

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;
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