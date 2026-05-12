<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Filter\ActiveTreatmentFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;

#[ApiResource(
    operations: [
        new GetCollection(
            name: 'get_all_without_pagination',
            uriTemplate: '/treatments/all',
            paginationEnabled: false,
        ),
        new GetCollection(),
        new Get(),
        new Post(),
        new Patch(),
        new Delete(),
    ],
)]
#[ORM\Entity]
#[ORM\Table(name: 'treatments', indexes: [
    new ORM\Index(name: 'idx_treatment_patient_id', columns: ['patient_id']),
    new ORM\Index(name: 'idx_treatment_patient_beg_dt', columns: ['patient_id', 'beg_dt']),
    new ORM\Index(name: 'idx_treatment_drug_id', columns: ['drug_id'])
])]
#[ApiFilter(ActiveTreatmentFilter::class)]
#[ApiFilter(SearchFilter::class, properties: [
    'patient' => 'exact',
])]
#[ApiFilter(OrderFilter::class, properties: ['begDt', 'realEndDt'])]
class Treatment
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $modDt = null;

    #[ORM\Column(type: 'integer', unique: true, nullable: true)]
    private ?int $code = null;

    #[Assert\NotBlank(message: 'treatment.diagnosis.not_blank')]
    #[ORM\Column(type: 'text', nullable: false)]
    private string $diagnosis;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comorbidities = null;

    #[Assert\GreaterThan(0, message: 'treatment.mnoFrom.greater_than_zero')]
    #[ORM\Column(type: 'float', nullable: false)]
    private float $mnoFrom;

    #[Assert\GreaterThan(0, message: 'treatment.mnoTo.greater_than_zero')]
    #[ORM\Column(type: 'float', nullable: false)]
    private float $mnoTo;

    #[Assert\Type(\DateTimeInterface::class, message: 'treatment.begDt.type')]
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
    
    #[Assert\NotBlank(message: 'treatment.drug.not_blank')]
    #[ORM\ManyToOne(targetEntity: Drug::class)]
    #[ORM\JoinColumn(name: 'drug_id', referencedColumnName: 'id', nullable: true)]
    private ?Drug $drug = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(type: 'integer', nullable: false, options: ['default' => 0])]
    private int $hemorrhages = 0;

    #[ORM\Column(type: 'integer', nullable: false, options: ['default' => 0])]
    private int $flags = 0;

    #[Assert\NotBlank(message: 'treatment.diagnosisCode.not_blank')]
    #[Assert\Length(max: 255, maxMessage: 'treatment.diagnosisCode.length')]
    #[ORM\Column(name: 'diagnosis_code', type: 'string', length: 255, nullable: true)]
    private ?string $diagnosisCode = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $pin = null;

    #[ORM\ManyToOne(targetEntity: Mkb10::class)]
    #[ORM\JoinColumn(name: 'mkb10_id', referencedColumnName: 'id', nullable: true)]
    private ?Mkb10 $mkb10 = null;

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

    public function getMkb10(): ?Mkb10
    {
        return $this->mkb10;
    }

    public function setMkb10(?Mkb10 $mkb10): self
    {
        $this->mkb10 = $mkb10;
        return $this;
    }

    #[Assert\Callback]
    public function validate(\Symfony\Component\Validator\Context\ExecutionContextInterface $context): void
    {
        if ($this->mnoFrom >= $this->mnoTo) {
            $context->buildViolation('treatment.mno_range.invalid')
                ->atPath('mnoFrom')
                ->addViolation();
        }

        if ($this->planEndDt !== null && $this->begDt !== null) {
            if ($this->planEndDt < $this->begDt) {
                $context->buildViolation('treatment.plan_end_dt.invalid')
                    ->atPath('planEndDt')
                    ->addViolation();
            }
        }
    }
}