<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use App\Filter\PatientDrugFilter;
use App\Filter\PatientGroupFilter;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ApiFilter(SearchFilter::class, properties: [
    'lastname' => 'ipartial',
    'hospital' => 'exact'
])]
#[ApiFilter(OrderFilter::class, properties: ['lastname'])]
#[ApiFilter(PatientGroupFilter::class, properties: [PatientGroupFilter::DRUG_GROUP_FILTER_NAME])]
#[ApiFilter(PatientDrugFilter::class, properties: ['drug'])]
#[ORM\Entity]
#[ORM\Table(name: 'patients')]
class Patient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $modDt = null;

    #[ORM\Column(type: 'text', nullable: false)]
    private string $firstname;

    #[ORM\Column(type: 'text', nullable: false)]
    private string $secondName;

    #[ORM\Column(type: 'text', nullable: false)]
    private string $lastname;

    #[ORM\Column(type: 'datetime', nullable: false)]
    private \DateTimeInterface $birthday;

    #[ORM\Column(type: 'integer', nullable: false, options: ['default' => 0])]
    private int $sex = 0;

    #[ORM\Column(type: 'text', nullable: false)]
    private string $smsPhone;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $address = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $passport = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $healthInsurance = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $snils = null;

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

    public function getSecondName(): string
    {
        return $this->secondName;
    }

    public function setSecondName(string $secondName): self
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
}