<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TestHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TestHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TestHistory::class);
    }

    public function findLatestByTreatmentIds(array $treatmentIds): array
    {
        if (empty($treatmentIds)) {
            return [];
        }

        $conn = $this->getEntityManager()->getConnection();
        $sql = '
            SELECT DISTINCT ON (treatment_id) id
            FROM test_history
            WHERE treatment_id IN (:treatmentIds)
            ORDER BY treatment_id, creation_dt DESC, id DESC
        ';
        $stmt = $conn->executeQuery($sql, ['treatmentIds' => $treatmentIds], ['treatmentIds' => \Doctrine\DBAL\ArrayParameterType::INTEGER]);
        $ids = $stmt->fetchFirstColumn();

        if (empty($ids)) {
            return [];
        }

        return $this->createQueryBuilder('th')
            ->where('th.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }
}