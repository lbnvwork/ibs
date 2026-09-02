<?php

declare(strict_types=1);

namespace Ibs\Context\LabIoTGateway\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Ibs\Context\PatientManagement\Entity\Patient;
use Doctrine\ORM\Mapping as ORM;
use Ibs\Context\LabIoTGateway\State\PatientVitalsLatestBatchProvider;

#[ApiResource(
    operations: [
        new GetCollection(),
        new GetCollection(
            uriTemplate: '/patient_vitals_latests/batch',
            provider: PatientVitalsLatestBatchProvider::class
        ),
        new Get(),
    ]
)]
#[ApiFilter(SearchFilter::class, properties: ['patient' => 'exact'])]
#[ORM\Entity]
#[ORM\Table(name: 'patient_vitals_latest', options: ['comment' => 'Последние витальные показатели'])]
#[ORM\UniqueConstraint(name: 'uniq_patient_id', columns: ['patient_id'])]
class PatientVitalsLatest
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', options: ['comment' => 'Идентификатор'])]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Patient::class)]
    #[ORM\JoinColumn(name: 'patient_id', referencedColumnName: 'id', nullable: false, unique: true, onDelete: 'CASCADE', options: ['comment' => 'Пациент'])]
    private Patient $patient;

    #[ORM\Column(type: 'float', nullable: true, options: ['comment' => 'Гемоглобин'])]
    private ?float $hb = null;

    #[ORM\Column(type: 'integer', nullable: true, options: ['comment' => 'Пульс'])]
    private ?int $heartRate = null;

    #[ORM\Column(type: 'integer', nullable: true, options: ['comment' => 'Систолическое давление'])]
    private ?int $systolicPressure = null;

    #[ORM\Column(type: 'integer', nullable: true, options: ['comment' => 'Диастолическое давление'])]
    private ?int $diastolicPressure = null;

    #[ORM\Column(type: 'integer', nullable: true, options: ['comment' => 'Сатурация'])]
    private ?int $saturation = null;

    #[ORM\Column(type: 'float', nullable: true, options: ['comment' => 'Вес'])]
    private ?float $weight = null;

    #[ORM\Column(type: 'float', nullable: true, options: ['comment' => 'Креатинин (мкмоль/л)'])]
    private ?float $creatinine = null;

    #[ORM\Column(type: 'datetime', options: ['comment' => 'Дата последнего обновления'])]
    private \DateTimeInterface $lastUpdated;

    public function __construct(Patient $patient)
    {
        $this->patient = $patient;
        $this->lastUpdated = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getWeight(): ?float
    {
        return $this->weight;
    }

    public function setWeight(?float $weight): self
    {
        $this->weight = $weight;
        return $this;
    }

    public function getCreatinine(): ?float
    {
        return $this->creatinine;
    }

    public function setCreatinine(?float $creatinine): self
    {
        $this->creatinine = $creatinine;
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
}