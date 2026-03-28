<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
    ],
    order: ['mkbCode' => 'ASC'],
    paginationEnabled: false,
)]
#[ORM\Entity]
#[ORM\Table(name: 'mkb10')]
class Mkb10
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $recCode = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $mkbCode = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $mkbName = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $idParent = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $addlCode = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $actual = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $date = null;

    // Геттеры и сеттеры (можно оставить развёрнутые)
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getRecCode(): ?string
    {
        return $this->recCode;
    }

    public function setRecCode(?string $recCode): self
    {
        $this->recCode = $recCode;
        return $this;
    }

    public function getMkbCode(): ?string
    {
        return $this->mkbCode;
    }

    public function setMkbCode(?string $mkbCode): self
    {
        $this->mkbCode = $mkbCode;
        return $this;
    }

    public function getMkbName(): ?string
    {
        return $this->mkbName;
    }

    public function setMkbName(?string $mkbName): self
    {
        $this->mkbName = $mkbName;
        return $this;
    }

    public function getIdParent(): ?int
    {
        return $this->idParent;
    }

    public function setIdParent(?int $idParent): self
    {
        $this->idParent = $idParent;
        return $this;
    }

    public function getAddlCode(): ?int
    {
        return $this->addlCode;
    }

    public function setAddlCode(?int $addlCode): self
    {
        $this->addlCode = $addlCode;
        return $this;
    }

    public function getActual(): ?int
    {
        return $this->actual;
    }

    public function setActual(?int $actual): self
    {
        $this->actual = $actual;
        return $this;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function setDate(?string $date): self
    {
        $this->date = $date;
        return $this;
    }
}