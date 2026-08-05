<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\State\TestHistoryLatestProvider;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\PreUpdate;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(
            name: 'latest_test_histories',
            uriTemplate: '/test_histories/latest',
            provider: TestHistoryLatestProvider::class,
            paginationEnabled: false,
        ),
        new GetCollection(),
        new Get(),
        new Post(),
        new Patch(),
        new Delete(),
    ],
)]
#[ORM\Entity]
#[ORM\Table(name: 'test_history')]
#[ApiFilter(SearchFilter::class, properties: ['treatment' => 'exact'])]
#[ApiFilter(OrderFilter::class, properties: ['creationDt'])]
class TestHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $modDt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $smsId = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $creationDt = null;

    #[ORM\Column(type: 'float', nullable: false)]
    #[Assert\NotBlank(message: 'Значение МНО обязательно')]
    #[Assert\GreaterThanOrEqual(value: 0.8, message: 'МНО должно быть не менее 0.8')]
    #[Assert\LessThanOrEqual(value: 10.0, message: 'МНО должно быть не более 10.0')]
    private float $mno = 0.0;

    #[ORM\ManyToOne(targetEntity: Treatment::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'treatment_id', referencedColumnName: 'id', nullable: true)]
    private ?Treatment $treatment = null;

    #[ORM\ManyToOne(targetEntity: Drug::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'drug_id', referencedColumnName: 'id', nullable: true)]
    private ?Drug $drug = null;

    #[ORM\Column(type: 'float', nullable: false)]
    #[Assert\NotBlank(message: 'Доза обязательна')]
    #[Assert\Positive(message: 'Доза должна быть положительной')]
    private float $doze = 0.0;

    #[ORM\Column(type: 'integer', nullable: false, options: ['default' => -1])]
    private int $doze2 = -1;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    // ----- lifecycle callbacks -----

    #[PrePersist]
    public function setCreatedAtValue(): void
    {
        if ($this->creationDt === null) {
            $this->creationDt = new \DateTime();
        }
        if ($this->modDt === null) {
            $this->modDt = new \DateTime();
        }
        // Гарантируем, что doze2 всегда -1, если не задан явно
        if ($this->doze2 === 0 || $this->doze2 === null) {
            $this->doze2 = -1;
        }
    }

    #[PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->modDt = new \DateTime();
    }

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

    public function getSmsId(): ?int
    {
        return $this->smsId;
    }

    public function setSmsId(?int $smsId): self
    {
        $this->smsId = $smsId;
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

    public function getMno(): float
    {
        return $this->mno;
    }

    public function setMno(float $mno): self
    {
        $this->mno = $mno;
        return $this;
    }

    public function getTreatment(): ?Treatment
    {
        return $this->treatment;
    }

    public function setTreatment(?Treatment $treatment): self
    {
        $this->treatment = $treatment;
        return $this;
    }

    public function getDrug(): ?Drug
    {
        return $this->drug;
    }

    public function setDrug(?Drug $drug): self
    {
        $this->drug = $drug;
        return $this;
    }

    public function getDoze(): float
    {
        return $this->doze;
    }

    public function setDoze(float $doze): self
    {
        $this->doze = $doze;
        return $this;
    }

    public function getDoze2(): int
    {
        return $this->doze2;
    }

    public function setDoze2(int $doze2): self
    {
        $this->doze2 = $doze2;
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