<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity]
#[ORM\Table(name: 'sms_in', options: ['comment' => 'Входящие SMS'])]
class SmsIn
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', nullable: true, options: ['comment' => 'Идентификатор'])]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime', nullable: true, options: ['comment' => 'Дата изменения'])]
    private ?\DateTimeInterface $modDt = null;

    #[ORM\Column(type: 'integer', nullable: true, options: ['comment' => 'ID лечения'])]
    private ?int $treatmentId = null;

    #[ORM\Column(type: 'integer', nullable: true, options: ['comment' => 'ID сервера'])]
    private ?int $serverId = null;

    #[ORM\Column(type: 'string', length: 11, nullable: true, options: ['comment' => 'Источник SMS'])]
    private ?string $smsSource = null;

    #[ORM\Column(type: 'string', length: 11, nullable: true, options: ['comment' => 'Номер'])]
    private ?string $num = null;

    #[ORM\Column(type: 'text', nullable: true, options: ['comment' => 'Текст'])]
    private ?string $text = null;

    #[ORM\Column(type: 'datetime', nullable: true, options: ['comment' => 'Дата создания'])]
    private ?\DateTimeInterface $creationDt = null;

    #[ORM\Column(type: 'integer', nullable: true, options: ['comment' => 'Статус'])]
    private ?int $status = null;

    #[ORM\Column(type: 'datetime', nullable: true, options: ['comment' => 'Дата'])]
    private ?\DateTimeInterface $dt = null;

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

    public function getServerId(): ?int
    {
        return $this->serverId;
    }

    public function setServerId(?int $serverId): self
    {
        $this->serverId = $serverId;
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

    public function getNum(): ?string
    {
        return $this->num;
    }

    public function setNum(?string $num): self
    {
        $this->num = $num;
        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): self
    {
        $this->text = $text;
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

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getDt(): ?\DateTimeInterface
    {
        return $this->dt;
    }

    public function setDt(?\DateTimeInterface $dt): self
    {
        $this->dt = $dt;
        return $this;
    }
}