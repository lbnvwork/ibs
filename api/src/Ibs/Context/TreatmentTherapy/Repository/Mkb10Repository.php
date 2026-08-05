<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Mkb10;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class Mkb10Repository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Mkb10::class);
    }

    public function findPopularActiveDiagnoses(int $limit = 10): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = '
            SELECT 
                m.id, 
                m.mkb_code, 
                m.mkb_name, 
                COUNT(t.id) as cnt
            FROM mkb10 m
                JOIN treatments t ON t.mkb10_id = m.id
            WHERE t.real_end_dt IS NULL
            GROUP BY m.id
            ORDER BY cnt DESC
            LIMIT :limit
        ';
        $stmt = $conn->executeQuery($sql, ['limit' => $limit]);
        $rows = $stmt->fetchAllAssociative();
        
        $result = [];
        foreach ($rows as $row) {
            $mkb10 = $this->find($row['id']);
            if ($mkb10) {
                $result[] = $mkb10;
            }
        }
        return $result;
    }

    public function searchByCodeOrName(string $query, int $limit = 20): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = '
            SELECT *
            FROM mkb10
            WHERE mkb_code ILIKE :query
            OR mkb_name ILIKE :query
            ORDER BY 
                CASE 
                    WHEN mkb_code = :query_exact THEN 1
                    WHEN mkb_code ILIKE :query_start THEN 2
                    ELSE 3
                END,
                mkb_code
            LIMIT :limit
        ';
        $stmt = $conn->executeQuery($sql, [
            'query' => "%$query%",
            'query_exact' => $query,
            'query_start' => "$query%",
            'limit' => $limit,
        ]);
        return $stmt->fetchAllAssociative();
    }
}