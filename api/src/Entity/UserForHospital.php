<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity]
#[ORM\Table(name: 'users_for_hospitals')]
class UserForHospital
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $id = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $permissions = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPermissions(): ?int
    {
        return $this->permissions;
    }

    public function setPermissions(?int $permissions): self
    {
        $this->permissions = $permissions;
        return $this;
    }
}
