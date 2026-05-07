<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;

#[ApiResource]
#[ORM\Entity]
#[ORM\Table(name: 'appointments')]
#[ApiFilter(SearchFilter::class, properties: ['treatment' => 'exact'])]
#[ApiFilter(OrderFilter::class, properties: ['appointmentDt'])]
class Appointment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $modDt = null;

    #[ORM\Column(type: 'datetime', nullable: false)]
    private \DateTimeInterface $appointmentDt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $creationDt = null;

    #[ORM\Column(type: 'float', nullable: false)]
    private float $doze;

    #[ORM\Column(type: 'text', nullable: false)]
    private string $doctorName;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(type: 'integer', nullable: false, options: ['default' => -1])]
    private int $doze2 = -1;

    #[ORM\ManyToOne(targetEntity: Treatment::class)]
    #[ORM\JoinColumn(name: 'treatment_id', referencedColumnName: 'id', nullable: true)]
    private ?Treatment $treatment = null;

    #[ORM\ManyToOne(targetEntity: SmsOut::class)]
    #[ORM\JoinColumn(name: 'sms_id', referencedColumnName: 'id', nullable: true)]
    private ?SmsOut $sms = null;

    #[ORM\ManyToOne(targetEntity: Drug::class)]
    #[ORM\JoinColumn(name: 'drug_id', referencedColumnName: 'id', nullable: true)]
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

    public function getAppointmentDt(): \DateTimeInterface
    {
        return $this->appointmentDt;
    }

    public function setAppointmentDt(\DateTimeInterface $appointmentDt): self
    {
        $this->appointmentDt = $appointmentDt;
        return $this;
    }

    public function getCreationDt(): ?\DateTimeInterface
    {
        return $this->creationDt;
    }

    public function setCreationDt(?\DateTimeInterface $creationDt): self
    {
        $this->creationDt = $creationDt;
        return $this;
    }

    public function getDoze(): float
    {
        return $this->doze;
    }

    public function setDoze(float $doze): self
    {
        $this->doze = $doze;
        return $this;
    }

    public function getDoctorName(): string
    {
        return $this->doctorName;
    }

    public function setDoctorName(string $doctorName): self
    {
        $this->doctorName = $doctorName;
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

    public function getDoze2(): int
    {
        return $this->doze2;
    }

    public function setDoze2(int $doze2): self
    {
        $this->doze2 = $doze2;
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

    public function getSms(): ?SmsOut
    {
        return $this->sms;
    }

    public function setSms(?SmsOut $sms): self
    {
        $this->sms = $sms;
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
}