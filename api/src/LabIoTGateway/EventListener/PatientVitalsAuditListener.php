<?php

declare(strict_types=1);

namespace App\LabIoTGateway\EventListener;

use App\LabIoTGateway\Entity\PatientVitals;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
class PatientVitalsAuditListener
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
    ) {}

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof PatientVitals) {
            return;
        }
        $username = $this->getCurrentUsername();
        $entity->setCreatedBy($username);
        $entity->setUpdatedBy($username);
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof PatientVitals) {
            return;
        }
        $entity->setUpdatedBy($this->getCurrentUsername());
    }

    private function getCurrentUsername(): ?string
    {
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return null;
        }
        $user = $token->getUser();
        return ($user instanceof UserInterface) ? $user->getUserIdentifier() : null;
    }
}