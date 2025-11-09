<?php

declare(strict_types=1);
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity]
#[ORM\Table(name: 'med_personnel_phones')]
class MedicalPersonnelPhone
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $modDt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $number = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

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

    public function getnumber(): ?string
    {
        return $this->number;
    }

    public function setnumber(?string $number): self
    {
        $this->number = $number;
        return $this;
    }

    public function getcomment(): ?string
    {
        return $this->comment;
    }

    public function setcomment(?string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

}
