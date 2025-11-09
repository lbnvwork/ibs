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

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $serverPacketId = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $balance = null;

    public function getid(): ?int
    {
        return $this->id;
    }

    public function setid(?int $id): self
    {
        $this->id = $id;
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

    public function getserverPacketId(): ?int
    {
        return $this->serverPacketId;
    }

    public function setserverPacketId(?int $serverPacketId): self
    {
        $this->serverPacketId = $serverPacketId;
        return $this;
    }

    public function getbalance(): ?string
    {
        return $this->balance;
    }

    public function setbalance(?string $balance): self
    {
        $this->balance = $balance;
        return $this;
    }

}
