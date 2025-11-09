<?php

declare(strict_types=1);
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity]
#[ORM\Table(name: 'treatments')]
class Treatment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $modDt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $code = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $diagnosis = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comorbidities = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $mnoFrom = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $mnoTo = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $begDt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $planEndDt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $realEndDt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $stoppingReason = null;

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

    public function getcode(): ?int
    {
        return $this->code;
    }

    public function setcode(?int $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getdiagnosis(): ?string
    {
        return $this->diagnosis;
    }

    public function setdiagnosis(?string $diagnosis): self
    {
        $this->diagnosis = $diagnosis;
        return $this;
    }

    public function getcomorbidities(): ?string
    {
        return $this->comorbidities;
    }

    public function setcomorbidities(?string $comorbidities): self
    {
        $this->comorbidities = $comorbidities;
        return $this;
    }

    public function getmnoFrom(): ?float
    {
        return $this->mnoFrom;
    }

    public function setmnoFrom(?float $mnoFrom): self
    {
        $this->mnoFrom = $mnoFrom;
        return $this;
    }

    public function getmnoTo(): ?float
    {
        return $this->mnoTo;
    }

    public function setmnoTo(?float $mnoTo): self
    {
        $this->mnoTo = $mnoTo;
        return $this;
    }

    public function getbegDt(): ?\DateTimeInterface
    {
        return $this->begDt;
    }

    public function setbegDt(?\DateTimeInterface $begDt): self
    {
        $this->begDt = $begDt;
        return $this;
    }

    public function getplanEndDt(): ?\DateTimeInterface
    {
        return $this->planEndDt;
    }

    public function setplanEndDt(?\DateTimeInterface $planEndDt): self
    {
        $this->planEndDt = $planEndDt;
        return $this;
    }

    public function getrealEndDt(): ?\DateTimeInterface
    {
        return $this->realEndDt;
    }

    public function setrealEndDt(?\DateTimeInterface $realEndDt): self
    {
        $this->realEndDt = $realEndDt;
        return $this;
    }

    public function getstoppingReason(): ?string
    {
        return $this->stoppingReason;
    }

    public function setstoppingReason(?string $stoppingReason): self
    {
        $this->stoppingReason = $stoppingReason;
        return $this;
    }

}
