<?php

declare(strict_types=1);
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity]
#[ORM\Table(name: 'sms_in')]
class SmsIn
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $modDt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $treatmentId = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $serverId = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $smsSource = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $num = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $text = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $creationDt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $status = null;

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

    public function gettreatmentId(): ?int
    {
        return $this->treatmentId;
    }

    public function settreatmentId(?int $treatmentId): self
    {
        $this->treatmentId = $treatmentId;
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

    public function getsmsSource(): ?int
    {
        return $this->smsSource;
    }

    public function setsmsSource(?int $smsSource): self
    {
        $this->smsSource = $smsSource;
        return $this;
    }

    public function getnum(): ?int
    {
        return $this->num;
    }

    public function setnum(?int $num): self
    {
        $this->num = $num;
        return $this;
    }

    public function gettext(): ?int
    {
        return $this->text;
    }

    public function settext(?int $text): self
    {
        $this->text = $text;
        return $this;
    }

    public function getcreationDt(): ?\DateTimeInterface
    {
        return $this->creationDt;
    }

    public function setcreationDt(?\DateTimeInterface $creationDt): self
    {
        $this->creationDt = $creationDt;
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

}
