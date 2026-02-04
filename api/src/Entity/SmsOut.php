<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity]
#[ORM\Table(name: 'sms_out')]
class SmsOut
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $modDt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $treatmentId = null;

    #[ORM\Column(type: 'string', length: 11, nullable: true)]
    private ?string $smsSource = null;

    #[ORM\Column(type: 'string', length: 11)]
    private string $smsTarget;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $creationDt = null;

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

    public function getTreatmentId(): ?int
    {
        return $this->treatmentId;
    }

    public function setTreatmentId(?int $treatmentId): self
    {
        $this->treatmentId = $treatmentId;
        return $this;
    }

    public function getSmsSource(): ?string
    {
        return $this->smsSource;
    }

    public function setSmsSource(?string $smsSource): self
    {
        $this->smsSource = $smsSource;
        return $this;
    }

    public function getSmsTarget(): string
    {
        return $this->smsTarget;
    }

    public function setSmsTarget(string $smsTarget): self
    {
        $this->smsTarget = $smsTarget;
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
}