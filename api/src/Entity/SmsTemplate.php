<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity]
#[ORM\Table(name: 'sms_templates')]
class SmsTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $id = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $smsType = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $smsSource = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $smsTemplate = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

}
