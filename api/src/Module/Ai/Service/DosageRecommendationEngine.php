<?php

declare(strict_types=1);

namespace App\Module\Ai\Service;

use App\Entity\Treatment;
use App\Entity\Appointment;
use App\Entity\TestHistory;
use Doctrine\ORM\EntityManagerInterface;

class DosageRecommendationEngine
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function recommend(int $treatmentId): array
    {
        /** @var Treatment|null $treatment */
        $treatment = $this->entityManager->getRepository(Treatment::class)->find($treatmentId);

        if (!$treatment) {
            return [
                'variants' => [],
                'explanation' => 'Лечение не найдено'
            ];
        }

        // 1. Получить последнее МНО
        $lastTestHistory = $this->entityManager->getRepository(TestHistory::class)
            ->findOneBy(
                ['treatment' => $treatment],
                ['creationDt' => 'DESC']
            );

        if (!$lastTestHistory) {
            return [
                'variants' => [],
                'explanation' => 'Нет данных МНО для расчёта'
            ];
        }

        $lastMno = $lastTestHistory->getMno();
        $mnoFrom = $treatment->getMnoFrom();
        $mnoTo = $treatment->getMnoTo();

        // 2. Получить текущую дозу (в таблетках)
        $currentDose = $this->getCurrentDose($treatment);
        if ($currentDose === null) {
            return [
                'variants' => [],
                'explanation' => 'Не удалось определить текущую дозу'
            ];
        }

        // 3. Применить правило коррекции
        $newDose = $currentDose;
        $explanation = '';

        if ($lastMno < $mnoFrom) {
            // Увеличить на 10%, но не более чем на 0.5 таб
            $increase = min($currentDose * 0.1, 0.5);
            $newDose = round($currentDose + $increase, 2);
            $explanation = sprintf(
                'МНО = %.2f ниже целевого диапазона (%.2f–%.2f). Доза увеличена на %.2f таб.',
                $lastMno, $mnoFrom, $mnoTo, $newDose - $currentDose
            );
        } elseif ($lastMno > $mnoTo) {
            // Уменьшить на 10%, но не менее чем на 0.5 таб
            $decrease = min($currentDose * 0.1, 0.5);
            $newDose = round($currentDose - $decrease, 2);
            $explanation = sprintf(
                'МНО = %.2f выше целевого диапазона (%.2f–%.2f). Доза уменьшена на %.2f таб.',
                $lastMno, $mnoFrom, $mnoTo, $currentDose - $newDose
            );
        } else {
            $newDose = $currentDose;
            $explanation = sprintf(
                'МНО = %.2f в целевом диапазоне (%.2f–%.2f). Доза оставлена без изменений.',
                $lastMno, $mnoFrom, $mnoTo
            );
        }

        // 4. Пересчитать в таблетки с шагом 0.25
        $newDose = round($newDose * 4) / 4; // округление до 0.25

        // 5. Сформировать три варианта
        $variants = [
            [
                'dose' => max(0, round($newDose, 2)),
                'label' => 'Основной'
            ],
            [
                'dose' => max(0, round($newDose - 0.25, 2)),
                'label' => 'Сниженный'
            ],
            [
                'dose' => round($newDose + 0.25, 2),
                'label' => 'Повышенный'
            ]
        ];

        return [
            'variants' => $variants,
            'explanation' => $explanation
        ];
    }

    private function getCurrentDose(Treatment $treatment): ?float
    {
        // Пробуем взять из последнего назначения (appointment)
        $lastAppointment = $this->entityManager->getRepository(Appointment::class)
            ->findOneBy(
                ['treatment' => $treatment],
                ['appointmentDt' => 'DESC']
            );

        if ($lastAppointment) {
            return $lastAppointment->getDoze();
        }

        // Если нет назначений, берём из последнего анализа
        $lastTest = $this->entityManager->getRepository(TestHistory::class)
            ->findOneBy(
                ['treatment' => $treatment],
                ['creationDt' => 'DESC']
            );

        if ($lastTest) {
            return $lastTest->getDoze();
        }

        return null;
    }
}