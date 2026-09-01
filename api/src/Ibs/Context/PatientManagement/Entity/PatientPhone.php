<?php

declare(strict_types=1);

namespace Ibs\Context\PatientManagement\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity]
#[ORM\Table(name: 'patient_phones', options: ['comment' => 'Телефоны пациентов'])]
class PatientPhone
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', nullable: true, options: ['comment' => 'Идентификатор'])]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime', nullable: true, options: ['comment' => 'Дата изменения'])]
    private ?\DateTimeInterface $modDt = null;

    #[ORM\Column(type: 'text', nullable: true, options: ['comment' => 'Номер телефона'])]
    private ?string $number = null;

    #[ORM\Column(type: 'text', nullable: true, options: ['comment' => 'Комментарий'])]
    private ?string $comment = null;

    #[ORM\ManyToOne(targetEntity: PhoneType::class)]
    #[ORM\JoinColumn(name: 'phone_type_id', referencedColumnName: 'id', options: ['comment' => 'Тип телефона'])]
    private ?PhoneType $phoneType = null;

    #[ORM\ManyToOne(targetEntity: Patient::class)]
    #[ORM\JoinColumn(name: 'person_id', referencedColumnName: 'id', options: ['comment' => 'Пациент'])]
    private ?Patient $person = null;

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

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $number): self
    {
        $this->number = $number;
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

    public function getPhoneType(): ?PhoneType
    {
        return $this->phoneType;
    }

    public function setPhoneType(?PhoneType $phoneType): self
    {
        $this->phoneType = $phoneType;
        return $this;
    }

    public function getPerson(): ?Patient
    {
        return $this->person;
    }

    public function setPerson(?Patient $person): self
    {
        $this->person = $person;
        return $this;
    }}