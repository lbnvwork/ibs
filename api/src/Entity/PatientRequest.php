<?php

declare(strict_types=1);
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity]
#[ORM\Table(name: 'patient_requests')]
class PatientRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $modDt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $creationDt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $reason = null;

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

    public function getcreationDt(): ?\DateTimeInterface
    {
        return $this->creationDt;
    }

    public function setcreationDt(?\DateTimeInterface $creationDt): self
    {
        $this->creationDt = $creationDt;
        return $this;
    }

    public function getreason(): ?string
    {
        return $this->reason;
    }

    public function setreason(?string $reason): self
    {
        $this->reason = $reason;
        return $this;
    }

}
