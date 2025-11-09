<?php

declare(strict_types=1);
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity]
#[ORM\Table(name: 'sms_templates')]
class SmsTemplate
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

    public function getid(): ?int
    {
        return $this->id;
    }

    public function setid(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getsmsType(): ?int
    {
        return $this->smsType;
    }

    public function setsmsType(?int $smsType): self
    {
        $this->smsType = $smsType;
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

    public function getsmsTemplate(): ?string
    {
        return $this->smsTemplate;
    }

    public function setsmsTemplate(?string $smsTemplate): self
    {
        $this->smsTemplate = $smsTemplate;
        return $this;
    }

    public function getcomment(): ?string
    {
        return $this->comment;
    }

    public function setcomment(?string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

}
