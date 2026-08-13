<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ibs\Context\Communication\Entity\NotificationLog;

/**
 * @extends ServiceEntityRepository<NotificationLog>
 */
class NotificationLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotificationLog::class);
    }

    public function save(NotificationLog $log, bool $flush = true): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($log);
        if ($flush) {
            $entityManager->flush();
        }
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    /**
     * @return NotificationLog[]
     */
    public function findByPatient(int $patientId): array
    {
        return $this->findBy(['patientId' => $patientId], ['createdAt' => 'DESC']);
    }

    /**
     * @return NotificationLog[]
     */
    public function findByTreatment(int $treatmentId): array
    {
        return $this->findBy(['treatmentId' => $treatmentId], ['createdAt' => 'DESC']);
    }
}
