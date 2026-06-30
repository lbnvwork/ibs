<?php

declare(strict_types=1);

namespace App\LabIoTGateway\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Entity\Patient;
use App\Entity\Treatment;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use App\LabIoTGateway\Validator\Constraints\AtLeastOneVitalSign;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ApiResource(
    operations: [
        new GetCollection(),
        new Post(),
        new Get(),
        new Put(),
    ],
    normalizationContext: ['groups' => ['vitals:read']],
    denormalizationContext: ['groups' => ['vitals:write']],
)]
#[ORM\Entity]
#[ORM\Table(name: 'patient_vitals')]
#[ORM\Index(
    name: 'idx_vitals_patient_record',
    columns: ['patient_id', 'record_dt'],
    options: ['order' => ['record_dt' => 'DESC']]
)]
#[ORM\HasLifecycleCallbacks]
#[AtLeastOneVitalSign]
class PatientVitals
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    #[Groups(['vitals:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Patient::class)]
    #[ORM\JoinColumn(name: 'patient_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    #[Groups(['vitals:read', 'vitals:write'])]
    private ?Patient $patient = null;

    #[ORM\ManyToOne(targetEntity: Treatment::class)]
    #[ORM\JoinColumn(name: 'treatment_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[Groups(['vitals:read', 'vitals:write'])]
    private ?Treatment $treatment = null;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotBlank]
    #[Assert\Type(\DateTimeInterface::class)]
    #[Groups(['vitals:read', 'vitals:write'])]
    private \DateTimeInterface $recordDt;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Assert\Positive]
    #[Assert\Range(min: 50, max: 250)]
    #[Groups(['vitals:read', 'vitals:write'])]
    private ?float $hb = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive]
    #[Assert\Range(min: 30, max: 250)]
    #[Groups(['vitals:read', 'vitals:write'])]
    private ?int $heartRate = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive]
    #[Assert\Range(min: 70, max: 250)]
    #[Groups(['vitals:read', 'vitals:write'])]
    private ?int $systolicPressure = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive]
    #[Assert\Range(min: 70, max: 250)]
    #[Groups(['vitals:read', 'vitals:write'])]
    private ?int $diastolicPressure = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive]
    #[Assert\Range(min: 50, max: 100)]
    #[Groups(['vitals:read', 'vitals:write'])]
    private ?int $saturation = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['vitals:read', 'vitals:write'])]
    private ?string $comment = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['vitals:read'])]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['vitals:read'])]
    private \DateTimeInterface $updatedAt;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Ignore] // не принимается от клиента и не возвращается
    private ?string $createdBy = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Ignore]
    private ?string $updatedBy = null;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Assert\Positive]
    #[Assert\Range(min: 20, max: 300)]
    #[Groups(['vitals:read', 'vitals:write'])]
    private ?float $weight = null;

    public function __construct()
    {
        $this->recordDt = new \DateTime();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTreatment(): ?Treatment
    {
        return $this->treatment;
    }

    public function setTreatment(?Treatment $treatment): self
    {
        $this->treatment = $treatment;
        return $this;
    }

    public function getRecordDt(): \DateTimeInterface
    {
        return $this->recordDt;
    }

    public function setRecordDt(\DateTimeInterface $recordDt): self
    {
        $this->recordDt = $recordDt;
        return $this;
    }

    public function getHb(): ?float
    {
        return $this->hb;
    }

    public function setHb(?float $hb): self
    {
        $this->hb = $hb;
        return $this;
    }

    public function getHeartRate(): ?int
    {
        return $this->heartRate;
    }

    public function setHeartRate(?int $heartRate): self
    {
        $this->heartRate = $heartRate;
        return $this;
    }

    public function getSystolicPressure(): ?int
    {
        return $this->systolicPressure;
    }

    public function setSystolicPressure(?int $systolicPressure): self
    {
        $this->systolicPressure = $systolicPressure;
        return $this;
    }

    public function getDiastolicPressure(): ?int
    {
        return $this->diastolicPressure;
    }

    public function setDiastolicPressure(?int $diastolicPressure): self
    {
        $this->diastolicPressure = $diastolicPressure;
        return $this;
    }

    public function getSaturation(): ?int
    {
        return $this->saturation;
    }

    public function setSaturation(?int $saturation): self
    {
        $this->saturation = $saturation;
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

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?string $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?string $updatedBy): self
    {
        $this->updatedBy = $updatedBy;
        return $this;
    }

    public function getWeight(): ?float
    {
        return $this->weight;
    }

    public function setWeight(?float $weight): self
    { 
        $this->weight = $weight;
        return $this; 
    }
}