<?php

declare(strict_types=1);

namespace App\ClinicalCore\Pharmacogenetics\Service;

use App\Entity\MarkerDrugRelation;
use App\Entity\PatientGeneticResult;
use App\Entity\Treatment;
use Doctrine\ORM\EntityManagerInterface;

class PharmacogeneticsService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function getPatientPharmacogenetics(int $patientId): array
    {
        // 1. Получить последнее активное лечение, иначе последнее завершённое
        $treatment = $this->getLatestTreatment($patientId);
        if (!$treatment) {
            return [];
        }

        $drug = $treatment->getDrug();
        if (!$drug) {
            return [];
        }

        // 2. Получить все связи маркеров с этим препаратом
        $relations = $this->entityManager->getRepository(MarkerDrugRelation::class)->findBy([
            'drug' => $drug,
        ]);

        if (empty($relations)) {
            return [];
        }

        // 3. Собрать маркеры и результаты
        $resultSet = [];
        foreach ($relations as $relation) {
            $marker = $relation->getMarker();
            if (!$marker) continue;

            // Найти существующий результат для этого пациента и маркера
            $existingResult = $this->entityManager->getRepository(PatientGeneticResult::class)->findOneBy([
                'patient' => $patientId,
                'marker'  => $marker,
            ]);

            $resultSet[] = [
                'markerId'       => $marker->getId(),
                'geneSymbol'     => $marker->getGeneSymbol(),
                'fullName'       => $marker->getFullName(),
                'rsId'           => $marker->getRsId(),
                'possibleValues' => $marker->getPossibleValues()->map(function($gmv) {
                    return [
                        'id'          => $gmv->getId(),
                        'value'       => $gmv->getValue(),
                        'label'       => $gmv->getLabel(),
                        'description' => $gmv->getDescription(),
                    ];
                })->toArray(),
                'currentValueId'  => $existingResult ? ($existingResult->getMarkerValue() ? $existingResult->getMarkerValue()->getId() : null) : null,
                'currentValue'    => $existingResult ? ($existingResult->getMarkerValue() ? $existingResult->getMarkerValue()->getValue() : null) : null,
                'testDate'        => $existingResult ? ($existingResult->getTestDate() ? $existingResult->getTestDate()->format('Y-m-d') : null) : null,
                'comment'         => $existingResult ? $existingResult->getComment() : null,
                'resultId'        => $existingResult ? $existingResult->getId() : null,
            ];
        }

        return $resultSet;
    }

    private function getLatestTreatment(int $patientId): ?Treatment
    {
        $repo = $this->entityManager->getRepository(Treatment::class);

        // Сначала ищем активное
        $qb = $repo->createQueryBuilder('t')
            ->where('t.patient = :patientId')
            ->andWhere('t.realEndDt IS NULL')
            ->orderBy('t.begDt', 'DESC')
            ->setMaxResults(1)
            ->setParameter('patientId', $patientId);

        $treatment = $qb->getQuery()->getOneOrNullResult();

        if ($treatment) {
            return $treatment;
        }

        // Если нет активного – последнее завершённое
        $qb = $repo->createQueryBuilder('t')
            ->where('t.patient = :patientId')
            ->orderBy('t.begDt', 'DESC')
            ->setMaxResults(1)
            ->setParameter('patientId', $patientId);

        return $qb->getQuery()->getOneOrNullResult();
    }
}