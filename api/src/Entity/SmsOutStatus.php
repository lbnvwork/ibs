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
    #[ORM\GeneratedValue]
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

    public function getid(): ?int
    {
        return $this->id;
    }

    public function setid(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getsmsId(): ?int
    {
        return $this->smsId;
    }

    public function setsmsId(?int $smsId): self
    {
        $this->smsId = $smsId;
        return $this;
    }

    public function getserverCode(): ?string
    {
        return $this->serverCode;
    }

    public function setserverCode(?string $serverCode): self
    {
        $this->serverCode = $serverCode;
        return $this;
    }

    public function getmodDt(): ?\DateTimeInterface
    {
        return $this->modDt;
    }

    public function setmodDt(?\DateTimeInterface $modDt): self
    {
        $this->modDt = $modDt;
        return $this;
    }

    public function getpacketId(): ?int
    {
        return $this->packetId;
    }

    public function setpacketId(?int $packetId): self
    {
        $this->packetId = $packetId;
        return $this;
    }

    public function getserverId(): ?int
    {
        return $this->serverId;
    }

    public function setserverId(?int $serverId): self
    {
        $this->serverId = $serverId;
        return $this;
    }

    public function getphoneZone(): ?int
    {
        return $this->phoneZone;
    }

    public function setphoneZone(?int $phoneZone): self
    {
        $this->phoneZone = $phoneZone;
        return $this;
    }

    public function getparts(): ?int
    {
        return $this->parts;
    }

    public function setparts(?int $parts): self
    {
        $this->parts = $parts;
        return $this;
    }

    public function getcredits(): ?int
    {
        return $this->credits;
    }

    public function setcredits(?int $credits): self
    {
        $this->credits = $credits;
        return $this;
    }

    public function getstatus(): ?int
    {
        return $this->status;
    }

    public function setstatus(?int $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function geterror(): ?string
    {
        return $this->error;
    }

    public function seterror(?string $error): self
    {
        $this->error = $error;
        return $this;
    }

}
