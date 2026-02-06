<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity]
#[ORM\Table(name: 'sms_out_packets')]
class SmsOutPacket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $modDt = null;

    #[ORM\Column(type: 'integer', nullable: false)]
    private int $serverPacketId;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $balance = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $serverCode = null;

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

    public function getServerPacketId(): int
    {
        return $this->serverPacketId;
    }

    public function setServerPacketId(int $serverPacketId): self
    {
        $this->serverPacketId = $serverPacketId;
        return $this;
    }

    public function getBalance(): ?string
    {
        return $this->balance;
    }

    public function setBalance(?string $balance): self
    {
        $this->balance = $balance;
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
}