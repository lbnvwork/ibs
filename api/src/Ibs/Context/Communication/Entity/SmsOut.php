<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity]
#[ORM\Table(name: 'sms_out', options: ['comment' => 'Исходящие SMS'])]
class SmsOut
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', nullable: true, options: ['comment' => 'Идентификатор'])]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime', nullable: true, options: ['comment' => 'Дата изменения'])]
    private ?\DateTimeInterface $modDt = null;

    #[ORM\Column(type: 'integer', nullable: true, options: ['comment' => 'ID лечения'])]
    private ?int $treatmentId = null;

    #[ORM\Column(type: 'string', length: 11, nullable: true, options: ['comment' => 'Источник SMS'])]
    private ?string $smsSource = null;

    #[ORM\Column(type: 'string', length: 11, options: ['comment' => 'Целевой номер'])]
    private string $smsTarget;

    #[ORM\Column(type: 'datetime', nullable: true, options: ['comment' => 'Дата создания'])]
    private ?\DateTimeInterface $creationDt = null;

    #[ORM\Column(type: 'text', nullable: false, options: ['comment' => 'Текст'])]
    private string $text;

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

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;
        return $this;
    }
}