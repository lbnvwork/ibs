<?php

declare(strict_types=1);

namespace Ibs\Context\PatientManagement\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * Справочник стадий хронической болезни почек (ХБП): 1..5.
 * Отсутствие ХБП кодируется отсутствием ссылки (null) в PatientAnamnesis.ckdStage.
 */
#[ApiResource]
#[ORM\Entity]
#[ORM\Table(name: 'ckd_stages', options: ['comment' => 'Стадии хронической болезни почек (справочник)'])]
class CkdStage
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', options: ['comment' => 'Идентификатор'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 50, options: ['comment' => 'Краткое название (Стадия N)'])]
    private string $name;

    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['comment' => 'Полное название (хроническая болезнь почек, стадия N)'])]
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
