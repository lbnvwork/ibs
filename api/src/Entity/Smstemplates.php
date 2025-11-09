<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity]
#[ORM\Table(name: 'smstemplates')]
class Smstemplates
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $id = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $smsType = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $smsSource = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $smsTemplate = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getSmsType(): ?int
    {
        return $this->smsType;
    }

    public function setSmsType(?int $smsType): self
    {
        $this->smsType = $smsType;
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

    public function getSmsTemplate(): ?string
    {
        return $this->smsTemplate;
    }

    public function setSmsTemplate(?string $smsTemplate): self
    {
        $this->smsTemplate = $smsTemplate;
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

}
