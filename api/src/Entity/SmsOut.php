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

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $smsSource = null;

    #[ORM\Column(type: 'integer')]
    private int $smsTarget;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $creationDt = null;

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

    public function getsmsSource(): ?string
    {
        return $this->smsSource;
    }

    public function setsmsSource(?string $smsSource): self
    {
        $this->smsSource = $smsSource;
        return $this;
    }

    public function getsmsTarget(): int
    {
        return $this->smsTarget;
    }

    public function setsmsTarget(int $smsTarget): self
    {
        $this->smsTarget = $smsTarget;
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

}
