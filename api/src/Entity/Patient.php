<?php

declare(strict_types=1);
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity]
#[ORM\Table(name: 'patients')]
class Patient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $modDt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $firstname = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $secondName = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $lastname = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $birthday = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $sex = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $smsPhone = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $address = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $passport = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $healthInsurance = null;

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

    public function getmodDt(): ?\DateTimeInterface
    {
        return $this->modDt;
    }

    public function setmodDt(?\DateTimeInterface $modDt): self
    {
        $this->modDt = $modDt;
        return $this;
    }

    public function getfirstname(): ?string
    {
        return $this->firstname;
    }

    public function setfirstname(?string $firstname): self
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getsecondname(): ?string
    {
        return $this->secondname;
    }

    public function setsecondname(?string $secondname): self
    {
        $this->secondname = $secondname;
        return $this;
    }

    public function getlastname(): ?string
    {
        return $this->lastname;
    }

    public function setlastname(?string $lastname): self
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getbirthday(): ?\DateTimeInterface
    {
        return $this->birthday;
    }

    public function setbirthday(?\DateTimeInterface $birthday): self
    {
        $this->birthday = $birthday;
        return $this;
    }

    public function getsex(): ?int
    {
        return $this->sex;
    }

    public function setsex(?int $sex): self
    {
        $this->sex = $sex;
        return $this;
    }

    public function getsmsPhone(): ?string
    {
        return $this->smsPhone;
    }

    public function setsmsPhone(?string $smsPhone): self
    {
        $this->smsPhone = $smsPhone;
        return $this;
    }

    public function getaddress(): ?string
    {
        return $this->address;
    }

    public function setaddress(?string $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function getpassport(): ?string
    {
        return $this->passport;
    }

    public function setpassport(?string $passport): self
    {
        $this->passport = $passport;
        return $this;
    }

    public function gethealthInsurance(): ?string
    {
        return $this->healthInsurance;
    }

    public function sethealthInsurance(?string $healthInsurance): self
    {
        $this->healthInsurance = $healthInsurance;
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
