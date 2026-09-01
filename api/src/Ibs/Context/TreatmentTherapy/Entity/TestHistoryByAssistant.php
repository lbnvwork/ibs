<?php

declare(strict_types=1);

namespace Ibs\Context\TreatmentTherapy\Entity;

use ApiPlatform\Metadata\ApiResource;
use Ibs\Context\SecurityIdentity\Entity\MedicalPersonnel;
use Ibs\Context\SecurityIdentity\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity]
#[ORM\Table(name: 'test_histories_by_assistant', options: ['comment' => 'Анализы (данные ассистента)'])]
class TestHistoryByAssistant
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', nullable: true, options: ['comment' => 'Идентификатор'])]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime', nullable: true, options: ['comment' => 'Дата изменения'])]
    private ?\DateTimeInterface $modDt = null;

    #[ORM\Column(type: 'datetime', nullable: true, options: ['comment' => 'Дата создания'])]
    private ?\DateTimeInterface $creationDt = null;

    #[ORM\Column(type: 'text', nullable: true, options: ['comment' => 'Комментарий'])]
    private ?string $comment = null;

    #[ORM\ManyToOne(targetEntity: TestHistory::class)]
    #[ORM\JoinColumn(name: 'test_history_id', referencedColumnName: 'id', options: ['comment' => 'Анализ (история МНО)'])]
    private ?TestHistory $testHistory = null;

    #[ORM\ManyToOne(targetEntity: MedicalPersonnel::class)]
    #[ORM\JoinColumn(name: 'assistant_id', referencedColumnName: 'id', options: ['comment' => 'Ассистент (медперсонал)'])]
    private ?MedicalPersonnel $assistant = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', options: ['comment' => 'Пользователь'])]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getModDt(): ?\DateTimeInterface
    {
        return $this->modDt;
    }

    public function setModDt(?\DateTimeInterface $modDt): self
    {
        $this->modDt = $modDt;
        return $this;
    }

    public function getCreationDt(): ?\DateTimeInterface
    {
        return $this->creationDt;
    }

    public function setCreationDt(?\DateTimeInterface $creationDt): self
    {
        $this->creationDt = $creationDt;
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

    public function getTestHistory(): ?TestHistory
    {
        return $this->testHistory;
    }

    public function setTestHistory(?TestHistory $testHistory): self
    {
        $this->testHistory = $testHistory;
        return $this;
    }

    public function getAssistant(): ?MedicalPersonnel
    {
        return $this->assistant;
    }

    public function setAssistant(?MedicalPersonnel $assistant): self
    {
        $this->assistant = $assistant;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }
}