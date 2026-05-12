<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use App\Filter\PatientDiagnosisFilter;
use App\Filter\PatientDrugFilter;
use App\Filter\PatientGroupFilter;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\PreUpdate;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource]
#[ApiFilter(SearchFilter::class, properties: [
    'lastname' => 'ipartial',
    'hospital' => 'exact'
])]
#[ApiFilter(OrderFilter::class, properties: ['lastname'])]
#[ApiFilter(PatientGroupFilter::class, properties: [PatientGroupFilter::DRUG_GROUP_FILTER_NAME])]
#[ApiFilter(PatientDrugFilter::class, properties: ['drug'])]
#[ApiFilter(PatientDiagnosisFilter::class, properties: ['diagnosisCode'])]
#[ORM\Entity]
#[ORM\Table(name: 'patients')]
class Patient
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $modDt = null;

    #[Assert\NotBlank(message: 'patient.firstname.not_blank')]
    #[Assert\Length(
        min: 2, 
        max: 255, 
        minMessage: 'patient.firstname.length', 
        maxMessage: 'patient.firstname.length'
    )]
    #[ORM\Column(type: 'text', nullable: false)]
    private string $firstname;

    #[Assert\Length(
        min: 2, 
        max: 255, 
        minMessage: 'patient.secondName.length', 
        maxMessage: 'patient.secondName.length'
    )]
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $secondName = null;

    #[Assert\NotBlank(message: 'patient.lastname.not_blank')]
    #[Assert\Length(
        min: 2, 
        max: 255, 
        minMessage: 'patient.lastname.length', 
        maxMessage: 'patient.lastname.length'
    )]
    #[ORM\Column(type: 'text', nullable: false)]
    private string $lastname;

    #[Assert\NotBlank(message: 'patient.birthday.not_blank')]
    #[Assert\Type(\DateTimeInterface::class, message: 'patient.birthday.type')]
    #[Assert\LessThanOrEqual(value: 'today', message: 'patient.birthday.max')]
    #[Assert\GreaterThanOrEqual(value: '-120 years', message: 'patient.birthday.min')]
    #[ORM\Column(type: 'datetime', nullable: false)]
    private \DateTimeInterface $birthday;

    #[Assert\NotBlank(message: 'patient.sex.not_blank')]
    #[Assert\Type(type: 'integer', message: 'patient.sex.type')]
    #[ORM\Column(type: 'integer', nullable: false, options: ['default' => 0])]
    private int $sex = 0;

    #[Assert\NotBlank(message: 'patient.smsPhone.not_blank')]
    #[Assert\Regex(pattern: '/^8\(\d{3}\)\d{3}-\d{2}-\d{2}$/', message: 'patient.smsPhone.regex')]
    #[ORM\Column(type: 'text', nullable: false)]
    private string $smsPhone;

    #[Assert\NotBlank(message: 'patient.address.not_blank')]
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $address = null;

    #[Assert\NotBlank(message: 'patient.passport.not_blank')]
    #[Assert\Regex(pattern: '/^\d{4} \d{6}$/', message: 'patient.passport.regex')]
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $passport = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $healthInsurance = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[Assert\NotBlank(message: 'patient.snils.not_blank')]
    #[Assert\Regex(pattern: '/^\d{3}-\d{3}-\d{3} \d{2}$/', message: 'patient.snils.regex')]
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $snils = null;

    #[Assert\NotBlank(message: 'patient.hospital.not_blank')]
    #[ORM\ManyToOne(targetEntity: Hospital::class)]
    #[ORM\JoinColumn(name: 'hospital_id', referencedColumnName: 'id', nullable: true)]
    private ?Hospital $hospital = null;

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

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getSecondName(): ?string
    {
        return $this->secondName;
    }

    public function setSecondName(?string $secondName): self
    {
        $this->secondName = $secondName;
        return $this;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getBirthday(): \DateTimeInterface
    {
        return $this->birthday;
    }

    public function setBirthday(\DateTimeInterface $birthday): self
    {
        $this->birthday = $birthday;
        return $this;
    }

    public function getSex(): int
    {
        return $this->sex;
    }

    public function setSex(int $sex): self
    {
        $this->sex = $sex;
        return $this;
    }

    public function getSmsPhone(): string
    {
        return $this->smsPhone;
    }

    public function setSmsPhone(string $smsPhone): self
    {
        $this->smsPhone = $smsPhone;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function getPassport(): ?string
    {
        return $this->passport;
    }

    public function setPassport(?string $passport): self
    {
        $this->passport = $passport;
        return $this;
    }

    public function getHealthInsurance(): ?string
    {
        return $this->healthInsurance;
    }

    public function setHealthInsurance(?string $healthInsurance): self
    {
        $this->healthInsurance = $healthInsurance;
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

    public function getSnils(): ?string
    {
        return $this->snils;
    }

    public function setSnils(?string $snils): self
    {
        $this->snils = $snils;
        return $this;
    }

    public function getHospital(): ?Hospital
    {
        return $this->hospital;
    }

    public function setHospital(?Hospital $hospital): self
    {
        $this->hospital = $hospital;
        return $this;
    }


    #[PrePersist]
    #[PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->modDt = new \DateTime();
    }
}