<?php

declare(strict_types=1);

namespace Ibs\Context\PatientManagement\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use Ibs\Context\TreatmentTherapy\Entity\Mkb10;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\PreUpdate;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Анамнез пациента: клапаны (митральный/аортальный/трикуспидальный/лёгочный),
 * сахарный диабет (СД), острое нарушение мозгового кровообращения (ОНМК),
 * хроническая болезнь почек (ХБП), острый коронарный синдром (ОКС) + шкалы
 * CHA₂DS₂-VASc (риск инсульта) / HAS-BLED (риск кровотечения) — готовые баллы
 * из регистра; пересчёт — FUNC-006, спринт 5.
 */
#[ApiResource]
#[ApiFilter(SearchFilter::class, properties: ['patient' => 'exact'])]
#[ORM\Entity]
#[ORM\Table(name: 'patient_anamnesis', options: ['comment' => 'Анамнез пациента'])]
class PatientAnamnesis
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', options: ['comment' => 'Идентификатор'])]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Patient::class)]
    #[ORM\JoinColumn(name: 'patient_id', referencedColumnName: 'id', nullable: false, unique: true, onDelete: 'CASCADE', options: ['comment' => 'Пациент'])]
    private ?Patient $patient = null;

    #[ORM\Column(type: 'boolean', nullable: true, options: ['comment' => 'Митральный клапан'])]
    private ?bool $mk = null;

    #[ORM\Column(type: 'boolean', nullable: true, options: ['comment' => 'Аортальный клапан'])]
    private ?bool $ak = null;

    #[ORM\Column(type: 'boolean', nullable: true, options: ['comment' => 'Трикуспидальный клапан'])]
    private ?bool $tk = null;

    #[ORM\Column(type: 'boolean', nullable: true, options: ['comment' => 'Лёгочный клапан'])]
    private ?bool $lk = null;

    #[ORM\ManyToOne(targetEntity: DiabetesType::class)]
    #[ORM\JoinColumn(name: 'diabetes_type_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL', options: ['comment' => 'Сахарный диабет (справочник)'])]
    private ?DiabetesType $diabetes = null;

    #[ORM\Column(type: 'boolean', nullable: true, options: ['comment' => 'Острое нарушение мозгового кровообращения (геморрагический)'])]
    private ?bool $strokeHemorrhagic = null;

    #[ORM\Column(type: 'boolean', nullable: true, options: ['comment' => 'Острое нарушение мозгового кровообращения (ишемический)'])]
    private ?bool $strokeIschemic = null;

    #[ORM\ManyToOne(targetEntity: CkdStage::class)]
    #[ORM\JoinColumn(name: 'ckd_stage_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL', options: ['comment' => 'Хроническая болезнь почек, стадия (справочник)'])]
    private ?CkdStage $ckdStage = null;

    #[ORM\ManyToOne(targetEntity: Mkb10::class)]
    #[ORM\JoinColumn(name: 'acs_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL', options: ['comment' => 'Острый коронарный синдром (код МКБ-10)'])]
    private ?Mkb10 $acs = null;

    #[Assert\PositiveOrZero(message: 'patient_anamnesis.cha2ds2Vasc.positive_or_zero')]
    #[ORM\Column(type: 'integer', nullable: true, options: ['comment' => 'Шкала CHA₂DS₂-VASc (риск инсульта, 0..9)'])]
    private ?int $cha2ds2Vasc = null;

    #[Assert\PositiveOrZero(message: 'patient_anamnesis.hasBled.positive_or_zero')]
    #[ORM\Column(type: 'integer', nullable: true, options: ['comment' => 'Шкала HAS-BLED (риск кровотечения, 0..9)'])]
    private ?int $hasBled = null;

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

    public function getMk(): ?bool
    {
        return $this->mk;
    }

    public function setMk(?bool $mk): self
    {
        $this->mk = $mk;
        return $this;
    }

    public function getAk(): ?bool
    {
        return $this->ak;
    }

    public function setAk(?bool $ak): self
    {
        $this->ak = $ak;
        return $this;
    }

    public function getTk(): ?bool
    {
        return $this->tk;
    }

    public function setTk(?bool $tk): self
    {
        $this->tk = $tk;
        return $this;
    }

    public function getLk(): ?bool
    {
        return $this->lk;
    }

    public function setLk(?bool $lk): self
    {
        $this->lk = $lk;
        return $this;
    }

    public function getDiabetes(): ?DiabetesType
    {
        return $this->diabetes;
    }

    public function setDiabetes(?DiabetesType $diabetes): self
    {
        $this->diabetes = $diabetes;
        return $this;
    }

    public function getStrokeHemorrhagic(): ?bool
    {
        return $this->strokeHemorrhagic;
    }

    public function setStrokeHemorrhagic(?bool $strokeHemorrhagic): self
    {
        $this->strokeHemorrhagic = $strokeHemorrhagic;
        return $this;
    }

    public function getStrokeIschemic(): ?bool
    {
        return $this->strokeIschemic;
    }

    public function setStrokeIschemic(?bool $strokeIschemic): self
    {
        $this->strokeIschemic = $strokeIschemic;
        return $this;
    }

    public function getCkdStage(): ?CkdStage
    {
        return $this->ckdStage;
    }

    public function setCkdStage(?CkdStage $ckdStage): self
    {
        $this->ckdStage = $ckdStage;
        return $this;
    }

    public function getAcs(): ?Mkb10
    {
        return $this->acs;
    }

    public function setAcs(?Mkb10 $acs): self
    {
        $this->acs = $acs;
        return $this;
    }

    public function getCha2ds2Vasc(): ?int
    {
        return $this->cha2ds2Vasc;
    }

    public function setCha2ds2Vasc(?int $cha2ds2Vasc): self
    {
        $this->cha2ds2Vasc = $cha2ds2Vasc;
        return $this;
    }

    public function getHasBled(): ?int
    {
        return $this->hasBled;
    }

    public function setHasBled(?int $hasBled): self
    {
        $this->hasBled = $hasBled;
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

    #[PrePersist]
    #[PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }
}
