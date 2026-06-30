<?php

declare(strict_types=1);

namespace App\LabIoTGateway\Entity;

use App\Entity\Patient;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'patient_vitals_latest')]
class PatientVitalsLatest
{
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: Patient::class)]
    #[ORM\JoinColumn(name: 'patient_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Patient $patient;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $hb = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $heartRate = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $systolicPressure = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $diastolicPressure = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $saturation = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $lastUpdated;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $weight = null;

    public function __construct(Patient $patient)
    {
        $this->patient = $patient;
        $this->lastUpdated = new \DateTime();
    }

    public function getPatient(): Patient
    {
        return $this->patient;
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

    public function getLastUpdated(): \DateTimeInterface
    {
        return $this->lastUpdated;
    }

    public function setLastUpdated(\DateTimeInterface $lastUpdated): self
    {
        $this->lastUpdated = $lastUpdated;
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