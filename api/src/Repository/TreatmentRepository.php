<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Treatment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Treatment>
 */
class TreatmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Treatment::class);
    }

    /**
     * Возвращает ID пациентов, у которых есть хотя бы одно активное лечение (realEndDt IS NULL)
     *
     * @param int[] $patientIds
     * @return int[]
     */
    public function getActivePatientIds(array $patientIds): array
    {
        if (empty($patientIds)) {
            return [];
        }

        $qb = $this->createQueryBuilder('t')
            ->select('IDENTITY(t.patient) as patient_id')
            ->where('t.patient IN (:ids)')
            ->andWhere('t.realEndDt IS NULL')
            ->setParameter('ids', $patientIds);

        $result = $qb->getQuery()->getScalarResult();

        return array_column($result, 'patient_id');
    }
}