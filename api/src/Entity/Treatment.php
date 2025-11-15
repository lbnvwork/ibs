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

    #[ORM\ManyToOne(targetEntity: Patient::class)]
    #[ORM\JoinColumn(name: 'patient_id', referencedColumnName: 'id')]
    private ?Patient $patient = null;

    #[ORM\ManyToOne(targetEntity: Drug::class)]
    #[ORM\JoinColumn(name: 'drug_id', referencedColumnName: 'id')]
    private ?Drug $drug = null;

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

    public function getCode(): ?int
    {
        return $this->code;
    }

    public function setCode(?int $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getDiagnosis(): ?string
    {
        return $this->diagnosis;
    }

    public function setDiagnosis(?string $diagnosis): self
    {
        $this->diagnosis = $diagnosis;
        return $this;
    }

    public function getComorbidities(): ?string
    {
        return $this->comorbidities;
    }

    public function setComorbidities(?string $comorbidities): self
    {
        $this->comorbidities = $comorbidities;
        return $this;
    }

    public function getMnoFrom(): ?float
    {
        return $this->mnoFrom;
    }

    public function setMnoFrom(?float $mnoFrom): self
    {
        $this->mnoFrom = $mnoFrom;
        return $this;
    }

    public function getMnoTo(): ?float
    {
        return $this->mnoTo;
    }

    public function setMnoTo(?float $mnoTo): self
    {
        $this->mnoTo = $mnoTo;
        return $this;
    }

    public function getBegDt(): ?\DateTimeInterface
    {
        return $this->begDt;
    }

    public function setBegDt(?\DateTimeInterface $begDt): self
    {
        $this->begDt = $begDt;
        return $this;
    }

    public function getPlanEndDt(): ?\DateTimeInterface
    {
        return $this->planEndDt;
    }

    public function setPlanEndDt(?\DateTimeInterface $planEndDt): self
    {
        $this->planEndDt = $planEndDt;
        return $this;
    }

    public function getRealEndDt(): ?\DateTimeInterface
    {
        return $this->realEndDt;
    }

    public function setRealEndDt(?\DateTimeInterface $realEndDt): self
    {
        $this->realEndDt = $realEndDt;
        return $this;
    }

    public function getStoppingReason(): ?string
    {
        return $this->stoppingReason;
    }

    public function setStoppingReason(?string $stoppingReason): self
    {
        $this->stoppingReason = $stoppingReason;
        return $this;
    }

    public function getPatient(): ?Patient
    {
        return $this->patient;
    }

    public function setPatient(?Patient $patient): self
    {
        $this->patient = $patient;
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
    }}