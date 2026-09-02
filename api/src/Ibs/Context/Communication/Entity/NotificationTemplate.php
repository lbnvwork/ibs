<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity]
#[ORM\Table(name: 'notification_templates', options: ['comment' => 'Шаблоны уведомлений'])]
#[ORM\UniqueConstraint(name: 'uniq_notification_template_code_channel', columns: ['code', 'channel'])]
class NotificationTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', nullable: true, options: ['comment' => 'Идентификатор'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 128, options: ['comment' => 'Код'])]
    private string $code;

    #[ORM\Column(type: 'string', length: 16, options: ['comment' => 'Канал'])]
    private string $channel;

    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['comment' => 'Тема'])]
    private ?string $subjectTemplate = null;

    #[ORM\Column(type: 'text', nullable: true, options: ['comment' => 'Тело'])]
    private ?string $bodyTemplate = null;

    #[ORM\Column(type: 'text', nullable: true, options: ['comment' => 'Описание'])]
    private ?string $description = null;

    public function __construct(string $code, string $channel)
    {
        $this->code = $code;
        $this->channel = $channel;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function setChannel(string $channel): self
    {
        $this->channel = $channel;
        return $this;
    }

    public function getSubjectTemplate(): ?string
    {
        return $this->subjectTemplate;
    }

    public function setSubjectTemplate(?string $subjectTemplate): self
    {
        $this->subjectTemplate = $subjectTemplate;
        return $this;
    }

    public function getBodyTemplate(): ?string
    {
        return $this->bodyTemplate;
    }

    public function setBodyTemplate(?string $bodyTemplate): self
    {
        $this->bodyTemplate = $bodyTemplate;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }
}
