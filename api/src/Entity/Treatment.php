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

    #[ORM\Column(type: 'integer', unique: true, nullable: true)]
    private ?int $code = null;

    #[ORM\Column(type: 'text', nullable: false)]
    private string $diagnosis;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comorbidities = null;

    #[ORM\Column(type: 'float', nullable: false)]
    private float $mnoFrom;

    #[ORM\Column(type: 'float', nullable: false)]
    private float $mnoTo;

    #[ORM\Column(type: 'datetime', nullable: false)]
    private \DateTimeInterface $begDt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $planEndDt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $realEndDt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $stoppingReason = null;

    #[ORM\ManyToOne(targetEntity: Patient::class)]
    #[ORM\JoinColumn(name: 'patient_id', referencedColumnName: 'id', nullable: true)]
    private ?Patient $patient = null;

    #[ORM\ManyToOne(targetEntity: Drug::class)]
    #[ORM\JoinColumn(name: 'drug_id', referencedColumnName: 'id', nullable: true)]
    private ?Drug $drug = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(type: 'integer', nullable: false, options: ['default' => 0])]
    private int $hemorrhages = 0;

    #[ORM\Column(type: 'integer', nullable: false, options: ['default' => 0])]
    private int $flags = 0;

    #[ORM\Column(name: 'diagnosis_code', type: 'string', length: 255, nullable: true)]
    private ?string $diagnosisCode = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $pin = null;

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

    public function getDiagnosis(): string
    {
        return $this->diagnosis;
    }

    public function setDiagnosis(string $diagnosis): self
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

    public function getMnoFrom(): float
    {
        return $this->mnoFrom;
    }

    public function setMnoFrom(float $mnoFrom): self
    {
        $this->mnoFrom = $mnoFrom;
        return $this;
    }

    public function getMnoTo(): float
    {
        return $this->mnoTo;
    }

    public function setMnoTo(float $mnoTo): self
    {
        $this->mnoTo = $mnoTo;
        return $this;
    }

    public function getBegDt(): \DateTimeInterface
    {
        return $this->begDt;
    }

    public function setBegDt(\DateTimeInterface $begDt): self
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
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    public function getHemorrhages(): int
    {
        return $this->hemorrhages;
    }

    public function setHemorrhages(int $hemorrhages): self
    {
        $this->hemorrhages = $hemorrhages;
        return $this;
    }

    public function getFlags(): int
    {
        return $this->flags;
    }

    public function setFlags(int $flags): self
    {
        $this->flags = $flags;
        return $this;
    }

    public function getDiagnosisCode(): ?string
    {
        return $this->diagnosisCode;
    }

    public function setDiagnosisCode(?string $diagnosisCode): self
    {
        $this->diagnosisCode = $diagnosisCode;
        return $this;
    }

    public function getPin(): ?int
    {
        return $this->pin;
    }

    public function setPin(?int $pin): self
    {
        $this->pin = $pin;
        return $this;
    }
}