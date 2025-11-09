<?php

declare(strict_types=1);
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity]
#[ORM\Table(name: 'med_personnel_users')]
class MedicalPersonnelUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $modDt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $login = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $password = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $roles = null;

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

    public function getlogin(): ?string
    {
        return $this->login;
    }

    public function setlogin(?string $login): self
    {
        $this->login = $login;
        return $this;
    }

    public function getpassword(): ?string
    {
        return $this->password;
    }

    public function setpassword(?string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getroles(): ?int
    {
        return $this->roles;
    }

    public function setroles(?int $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

}
