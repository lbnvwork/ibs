<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Защищённый токен диплинка MAX для пациента.
 *
 * Диплинк вида `https://max.ru/<бот>?start=<token>` выдаётся пациенту, чтобы он
 * привязал свой chat_id в MAX к пациенту. Токен случайный, поэтому подобрать
 * чужой patientId перебором нельзя.
 */
#[ORM\Entity]
#[ORM\Table(name: 'max_deeplinks', options: ['comment' => 'Диплинки MAX'])]
#[ORM\UniqueConstraint(name: 'uniq_max_deeplink_patient', columns: ['patient_id'])]
#[ORM\UniqueConstraint(name: 'uniq_max_deeplink_token', columns: ['token'])]
class MaxDeepLink
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', nullable: true, options: ['comment' => 'Идентификатор'])]
    private ?int $id = null;

    #[ORM\Column(type: 'integer', nullable: false, options: ['comment' => 'ID пациента'])]
    private int $patientId;

    #[ORM\Column(type: 'string', length: 64, nullable: false, options: ['comment' => 'Токен'])]
    private string $token;

    #[ORM\Column(type: 'datetime', options: ['comment' => 'Дата создания'])]
    private \DateTimeInterface $createdAt;

    public function __construct(int $patientId, string $token)
    {
        $this->patientId = $patientId;
        $this->token = $token;
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPatientId(): int
    {
        return $this->patientId;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }
}
