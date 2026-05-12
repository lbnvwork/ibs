<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity]
#[ORM\Table(name: 'drugs', indexes: [
    new ORM\Index(name: 'idx_drug_group_id', columns: ['group_id'])
])]
class Drug
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $modDt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $nominative = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $genitive = null;

    #[ORM\ManyToOne(targetEntity: DrugGroup::class)]
    #[ORM\JoinColumn(name: 'group_id', referencedColumnName: 'id')]
    private ?DrugGroup $group = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getModDt(): ?\DateTimeInterface
    {
        return $this->modDt;
    }

    public function setModDt(?\DateTimeInterface $modDt): self
    {
        $this->modDt = $modDt;
        return $this;
    }

    public function getNominative(): ?string
    {
        return $this->nominative;
    }

    public function setNominative(?string $nominative): self
    {
        $this->nominative = $nominative;
        return $this;
    }

    public function getGenitive(): ?string
    {
        return $this->genitive;
    }

    public function setGenitive(?string $genitive): self
    {
        $this->genitive = $genitive;
        return $this;
    }

    public function getGroup(): ?DrugGroup
    {
        return $this->group;
    }

    public function setGroup(?DrugGroup $group): void
    {
        $this->group = $group;
    }
}
