<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ibs\Context\Communication\Entity\MaxDeepLink;

/**
 * @extends ServiceEntityRepository<MaxDeepLink>
 */
class MaxDeepLinkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MaxDeepLink::class);
    }

    public function findByPatientId(int $patientId): ?MaxDeepLink
    {
        return $this->findOneBy(['patientId' => $patientId]);
    }

    public function findByToken(string $token): ?MaxDeepLink
    {
        return $this->findOneBy(['token' => $token]);
    }
}
