<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity]
#[ORM\Table(name: 'holidays')]
class Holiday
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $modDt = null;

    #[ORM\Column(type: 'integer', nullable: false)]
    private int $hMonth;

    #[ORM\Column(type: 'integer', nullable: false)]
    private int $hDay;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(type: 'integer', nullable: false, options: ['default' => 2015])]
    private int $hYear = 2015;

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

    public function getHMonth(): int
    {
        return $this->hMonth;
    }

    public function setHMonth(int $hMonth): self
    {
        $this->hMonth = $hMonth;
        return $this;
    }

    public function getHDay(): int
    {
        return $this->hDay;
    }

    public function setHDay(int $hDay): self
    {
        $this->hDay = $hDay;
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

    public function getHYear(): int
    {
        return $this->hYear;
    }

    public function setHYear(int $hYear): self
    {
        $this->hYear = $hYear;
        return $this;
    }
}