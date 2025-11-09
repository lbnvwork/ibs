<?php

declare(strict_types=1);
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity]
#[ORM\Table(name: 'drugs')]
class Drug
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $modDt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $nominative = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $genitive = null;

    public function getid(): ?int
    {
        return $this->id;
    }

    public function setid(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getmodDt(): ?\DateTimeInterface
    {
        return $this->modDt;
    }

    public function setmodDt(?\DateTimeInterface $modDt): self
    {
        $this->modDt = $modDt;
        return $this;
    }

    public function getnominative(): ?string
    {
        return $this->nominative;
    }

    public function setnominative(?string $nominative): self
    {
        $this->nominative = $nominative;
        return $this;
    }

    public function getgenitive(): ?string
    {
        return $this->genitive;
    }

    public function setgenitive(?string $genitive): self
    {
        $this->genitive = $genitive;
        return $this;
    }

}
