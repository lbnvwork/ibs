<?php

declare(strict_types=1);

namespace Ibs\Context\SecurityIdentity\Entity;

use ApiPlatform\Metadata\ApiResource;
use Ibs\Context\PatientManagement\Entity\Hospital;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity]
#[ORM\Table(name: 'medical_personnel', options: ['comment' => 'Медицинский персонал'])]
class MedicalPersonnel
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', nullable: true, options: ['comment' => 'Идентификатор'])]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime', nullable: true, options: ['comment' => 'Дата изменения'])]
    private ?\DateTimeInterface $modDt = null;

    #[ORM\Column(type: 'text', nullable: true, options: ['comment' => 'ФИО'])]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true, options: ['comment' => 'Должность'])]
    private ?string $post = null;

    #[ORM\Column(type: 'text', nullable: true, options: ['comment' => 'Адрес'])]
    private ?string $address = null;

    #[ORM\Column(type: 'text', nullable: true, options: ['comment' => 'Комментарий'])]
    private ?string $comment = null;

    #[ORM\ManyToOne(targetEntity: Hospital::class)]
    #[ORM\JoinColumn(name: 'hospital_id', referencedColumnName: 'id', options: ['comment' => 'Больница'])]
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getPost(): ?string
    {
        return $this->post;
    }

    public function setPost(?string $post): self
    {
        $this->post = $post;
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

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;
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
    }}