<?php

declare(strict_types=1);

namespace Ibs\Context\AdaptivePlanning\Entity;

use ApiPlatform\Metadata\ApiResource;
use Ibs\Context\PatientManagement\Entity\Hospital;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity]
#[ORM\Table(name: 'hospital_test_plans', options: ['comment' => 'Планы анализов по больницам'])]
class HospitalTestPlan
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', nullable: true, options: ['comment' => 'Идентификатор'])]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime', nullable: true, options: ['comment' => 'Дата изменения'])]
    private ?\DateTimeInterface $modDt = null;

    #[ORM\Column(type: 'datetime', nullable: true, options: ['comment' => 'Дата создания'])]
    private ?\DateTimeInterface $creationDt = null;

    #[ORM\Column(type: 'datetime', nullable: true, options: ['comment' => 'Дата явки (замера)'])]
    private ?\DateTimeInterface $testDt = null;

    #[ORM\Column(type: 'integer', nullable: true, options: ['comment' => 'Статус'])]
    private ?int $status = null;

    #[ORM\Column(type: 'text', nullable: true, options: ['comment' => 'Комментарий'])]
    private ?string $comment = null;

    #[ORM\ManyToOne(targetEntity: Hospital::class)]
    #[ORM\JoinColumn(name: 'hospital_id', referencedColumnName: 'id', options: ['comment' => 'Больница (ЛПУ)'])]
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

    public function getCreationDt(): ?\DateTimeInterface
    {
        return $this->creationDt;
    }

    public function setCreationDt(?\DateTimeInterface $creationDt): self
    {
        $this->creationDt = $creationDt;
        return $this;
    }

    public function getTestDt(): ?\DateTimeInterface
    {
        return $this->testDt;
    }

    public function setTestDt(?\DateTimeInterface $testDt): self
    {
        $this->testDt = $testDt;
        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): self
    {
        $this->status = $status;
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