<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity]
#[ORM\Table(name: 'sms_out_statuses')]
class SmsOutStatus
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $id = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $smsId = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $serverCode = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $modDt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $packetId = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $serverId = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $phoneZone = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $parts = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $credits = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $status = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $error = null;

    #[ORM\ManyToOne(targetEntity: SmsOut::class)]
    #[ORM\JoinColumn(name: 'sms_id', referencedColumnName: 'id')]
    private ?SmsOut $smsOut = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSmsId(): ?int
    {
        return $this->smsId;
    }

    public function setSmsId(?int $smsId): self
    {
        $this->smsId = $smsId;
        return $this;
    }

    public function getServerCode(): ?string
    {
        return $this->serverCode;
    }

    public function setServerCode(?string $serverCode): self
    {
        $this->serverCode = $serverCode;
        return $this;
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

    public function getPacketId(): ?int
    {
        return $this->packetId;
    }

    public function setPacketId(?int $packetId): self
    {
        $this->packetId = $packetId;
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

    public function getPhoneZone(): ?int
    {
        return $this->phoneZone;
    }

    public function setPhoneZone(?int $phoneZone): self
    {
        $this->phoneZone = $phoneZone;
        return $this;
    }

    public function getParts(): ?int
    {
        return $this->parts;
    }

    public function setParts(?int $parts): self
    {
        $this->parts = $parts;
        return $this;
    }

    public function getCredits(): ?int
    {
        return $this->credits;
    }

    public function setCredits(?int $credits): self
    {
        $this->credits = $credits;
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

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(?string $error): self
    {
        $this->error = $error;
        return $this;
    }

    public function getSmsOut(): ?SmsOut
    {
        return $this->smsOut;
    }

    public function setSmsOut(?SmsOut $smsOut): self
    {
        $this->smsOut = $smsOut;
        return $this;
    }}