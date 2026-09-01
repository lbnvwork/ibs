<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity]
#[ORM\Table(name: 'sms_templates', options: ['comment' => 'Шаблоны SMS'])]
class SmsTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', nullable: true, options: ['comment' => 'Идентификатор'])]
    private ?int $id = null;

    #[ORM\Column(type: 'integer', nullable: true, options: ['comment' => 'Тип SMS'])]
    private ?int $smsType = null;

    #[ORM\Column(type: 'text', nullable: true, options: ['comment' => 'Источник SMS'])]
    private ?string $smsSource = null;

    #[ORM\Column(type: 'text', nullable: true, options: ['comment' => 'Шаблон SMS'])]
    private ?string $smsTemplate = null;

    #[ORM\Column(type: 'text', nullable: true, options: ['comment' => 'Комментарий'])]
    private ?string $comment = null;

    public function getId(): ?int
    {
        return $this->id;
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
