<?php

declare(strict_types=1);

namespace App\LabIoTGateway\EventListener;

use App\LabIoTGateway\Entity\PatientVitals;
use App\LabIoTGateway\Service\PatientVitalsSyncService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
class PatientVitalsListener
{
    public function __construct(
        private PatientVitalsSyncService $syncService,
    ) {}

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->syncIfVitals($args->getObject());
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->syncIfVitals($args->getObject());
    }

    private function syncIfVitals(object $entity): void
    {
        if ($entity instanceof PatientVitals) {
            $this->syncService->syncFromVitals($entity);
        }
    }
}