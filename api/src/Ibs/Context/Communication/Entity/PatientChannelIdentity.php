<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(),
        new Post(),
        new Get(),
        new Put(),
        new Delete(),
    ],
    normalizationContext: ['groups' => ['patient_channel_identity:read']],
    denormalizationContext: ['groups' => ['patient_channel_identity:write']],
)]
#[ApiFilter(SearchFilter::class, properties: ['patientId' => 'exact'])]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'patient_channel_identities')]
#[ORM\UniqueConstraint(name: 'uniq_patient_channel_identity', columns: ['patient_id', 'channel_type'])]
class PatientChannelIdentity
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['patient_channel_identity:read'])]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'patient_channel_identity.patient_id.not_blank')]
    #[ORM\Column(type: 'integer', nullable: false)]
    #[Groups(['patient_channel_identity:read', 'patient_channel_identity:write'])]
    private int $patientId;

    #[Assert\NotBlank(message: 'patient_channel_identity.channel_type.not_blank')]
    #[Assert\Length(max: 32, maxMessage: 'patient_channel_identity.channel_type.length')]
    #[Assert\Choice(choices: ['sms', 'email', 'push', 'max'], message: 'patient_channel_identity.channel_type.choice')]
    #[ORM\Column(type: 'string', length: 32, nullable: false)]
    #[Groups(['patient_channel_identity:read', 'patient_channel_identity:write'])]
    private string $channelType;

    #[Assert\NotBlank(message: 'patient_channel_identity.value.not_blank')]
    #[Assert\Length(max: 255, maxMessage: 'patient_channel_identity.value.length')]
    #[Assert\Regex(pattern: '/^[^\s<>]+$/', message: 'patient_channel_identity.value.regex')]
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    #[Groups(['patient_channel_identity:read', 'patient_channel_identity:write'])]
    private string $value;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['patient_channel_identity:read'])]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['patient_channel_identity:read'])]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPatientId(): int
    {
        return $this->patientId;
    }

    public function setPatientId(int $patientId): self
    {
        $this->patientId = $patientId;

        return $this;
    }

    public function getChannelType(): string
    {
        return $this->channelType;
    }

    public function setChannelType(string $channelType): self
    {
        $this->channelType = trim($channelType);

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = trim($value);

        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }
}