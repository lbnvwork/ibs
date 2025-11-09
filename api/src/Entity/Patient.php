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

}
