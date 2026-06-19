<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\ClinicalCore\Pharmacogenetics\State\PatientGeneticResultProcessor;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    operations: [
        new GetCollection(),
        new Post(processor: PatientGeneticResultProcessor::class),
        new Get(),
        new Put(processor: PatientGeneticResultProcessor::class),
        new Delete(),
    ]
)]
#[ORM\Entity]
#[ORM\Table(name: 'patient_genetic_results')]
#[ORM\UniqueConstraint(name: 'uniq_patient_marker', columns: ['patient_id', 'marker_id'])]
#[ApiFilter(SearchFilter::class, properties: ['patient' => 'exact'])]
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

    #[ORM\ManyToOne(targetEntity: GeneticMarkerValue::class)]
    #[ORM\JoinColumn(name: 'marker_value_id', referencedColumnName: 'id', nullable: false)]
    private ?GeneticMarkerValue $markerValue = null;

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

    public function getMarkerValue(): ?GeneticMarkerValue
    {
        return $this->markerValue;
    }

    public function setMarkerValue(?GeneticMarkerValue $markerValue): self
    {
        $this->markerValue = $markerValue;
        return $this;
    }
}