<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Entity;

use Doctrine\ORM\Mapping as ORM;
    
#[ORM\Entity]
#[ORM\Table(name: 'notification_logs', options: ['comment' => 'Логи уведомлений'])]
#[ORM\Index(name: 'idx_notification_log_patient_id', columns: ['patient_id'])]
#[ORM\Index(name: 'idx_notification_log_treatment_id', columns: ['treatment_id'])]
class NotificationLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', nullable: true, options: ['comment' => 'Идентификатор'])]
    private ?int $id = null;

    #[ORM\Column(type: 'integer', nullable: true, options: ['comment' => 'ID пациента'])]
    private ?int $patientId = null;

    #[ORM\Column(type: 'integer', nullable: true, options: ['comment' => 'ID лечения'])]
    private ?int $treatmentId = null;

    #[ORM\Column(type: 'string', length: 32, nullable: true, options: ['comment' => 'Канал связи'])]
    private ?string $channelType = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['comment' => 'Адрес получателя'])]
    private ?string $recipientAddress = null;

    #[ORM\Column(type: 'string', length: 16, nullable: true, options: ['comment' => 'Приоритет'])]
    private ?string $priority = null;

    #[ORM\Column(type: 'string', length: 64, nullable: true, options: ['comment' => 'Код шаблона'])]
    private ?string $templateCode = null;

    #[ORM\Column(type: 'string', length: 16, nullable: true, options: ['comment' => 'Статус'])]
    private ?string $status = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['comment' => 'Внешний ID'])]
    private ?string $externalId = null;

    #[ORM\Column(type: 'text', nullable: true, options: ['comment' => 'Текст ошибки'])]
    private ?string $errorMessage = null;

    #[ORM\Column(type: 'datetime', nullable: true, options: ['comment' => 'Дата создания'])]
    private ?\DateTimeInterface $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPatientId(): ?int
    {
        return $this->patientId;
    }

    public function setPatientId(?int $patientId): self
    {
        $this->patientId = $patientId;
        return $this;
    }

    public function getTreatmentId(): ?int
    {
        return $this->treatmentId;
    }

    public function setTreatmentId(?int $treatmentId): self
    {
        $this->treatmentId = $treatmentId;
        return $this;
    }

    public function getChannelType(): ?string
    {
        return $this->channelType;
    }

    public function setChannelType(?string $channelType): self
    {
        $this->channelType = $channelType;
        return $this;
    }

    public function getRecipientAddress(): ?string
    {
        return $this->recipientAddress;
    }

    public function setRecipientAddress(?string $recipientAddress): self
    {
        $this->recipientAddress = $recipientAddress;
        return $this;
    }

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(?string $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    public function getTemplateCode(): ?string
    {
        return $this->templateCode;
    }

    public function setTemplateCode(?string $templateCode): self
    {
        $this->templateCode = $templateCode;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(?string $externalId): self
    {
        $this->externalId = $externalId;
        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
