<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'patient_genetic_results')]
#[ORM\UniqueConstraint(name: 'uniq_patient_marker', columns: ['patient_id', 'marker_id'])]
class PatientGeneticResult
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Patient::class)]
    #[ORM\JoinColumn(name: 'patient_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Patient $patient = null;

    #[ORM\ManyToOne(targetEntity: GeneticMarker::class)]
    #[ORM\JoinColumn(name: 'marker_id', referencedColumnName: 'id', nullable: false)]
    private ?GeneticMarker $marker = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $value;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $testDate = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
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

    public function getMarker(): ?GeneticMarker
    {
        return $this->marker;
    }

    public function setMarker(?GeneticMarker $marker): self
    {
        $this->marker = $marker;
        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getTestDate(): ?\DateTimeInterface
    {
        return $this->testDate;
    }

    public function setTestDate(?\DateTimeInterface $testDate): self
    {
        $this->testDate = $testDate;
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

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}