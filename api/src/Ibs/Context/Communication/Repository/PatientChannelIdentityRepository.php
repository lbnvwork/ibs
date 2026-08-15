<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ibs\Context\Communication\Entity\PatientChannelIdentity;

/**
 * @extends ServiceEntityRepository<PatientChannelIdentity>
 */
class PatientChannelIdentityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PatientChannelIdentity::class);
    }

    public function findOneByPatientAndChannel(int $patientId, string $channelType): ?PatientChannelIdentity
    {
        return $this->findOneBy([
            'patientId' => $patientId,
            'channelType' => $channelType,
        ]);
    }

    /**
     * @return PatientChannelIdentity[]
     */
    public function findByPatient(int $patientId): array
    {
        return $this->findBy(['patientId' => $patientId], ['channelType' => 'ASC']);
    }
}