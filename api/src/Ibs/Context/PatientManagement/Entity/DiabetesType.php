<?php

declare(strict_types=1);

namespace Ibs\Context\PatientManagement\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * Справочник типов сахарного диабета (СД): 1 — СД 1 типа, 2 — СД 2 типа.
 * «Нет диабета» кодируется отсутствием ссылки (null) в PatientAnamnesis.diabetes.
 */
#[ApiResource]
#[ORM\Entity]
#[ORM\Table(name: 'diabetes_types', options: ['comment' => 'Типы сахарного диабета (справочник)'])]
class DiabetesType
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', options: ['comment' => 'Идентификатор'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 50, options: ['comment' => 'Краткое название (СД1/СД2)'])]
    private string $name;

    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['comment' => 'Полное название (сахарный диабет 1/2 типа)'])]
    private ?string $fullName = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(?string $fullName): self
    {
        $this->fullName = $fullName;

        return $this;
    }
}
