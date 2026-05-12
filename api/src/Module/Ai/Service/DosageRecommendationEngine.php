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
        $treatment = $this->entityManager->getRepository(Treatment::class)->find($treatmentId);
        if (!$treatment) {
            return ['variants' => [], 'explanation' => 'Лечение не найдено'];
        }

        $lastTestHistory = $this->entityManager->getRepository(TestHistory::class)
            ->findOneBy(['treatment' => $treatment], ['creationDt' => 'DESC']);
        if (!$lastTestHistory) {
            return ['variants' => [], 'explanation' => 'Нет данных МНО для расчёта'];
        }

        $lastMno = $lastTestHistory->getMno();
        $mnoFrom = $treatment->getMnoFrom();
        $mnoTo   = $treatment->getMnoTo();

        // Особые случаи
        if ($lastMno > 5.0) {
            return [
                'variants' => [],
                'explanation' => 'МНО превышает 5.0. Рекомендуется пропустить приём и связаться с врачом.',
            ];
        }
        if ($lastMno < 1.5) {
            // Заглушка: проверка механического клапана будет позже
            return [
                'variants' => [],
                'explanation' => 'МНО ниже 1.5. Требуется срочная консультация врача.',
            ];
        }

        // Получить текущую дозу (в таблетках)
        $currentDose = $this->getCurrentDose($treatment);
        if ($currentDose === null) {
            return ['variants' => [], 'explanation' => 'Не удалось определить текущую дозу'];
        }

        $newDose = $currentDose;
        $explanation = '';

        if ($lastMno < $mnoFrom) {
            $increase = min($currentDose * 0.1, 0.5);
            $newDose = round($currentDose + $increase, 2);
            $explanation = sprintf(
                'МНО = %.2f ниже целевого диапазона (%.2f–%.2f). Доза увеличена на %.2f таб.',
                $lastMno, $mnoFrom, $mnoTo, $newDose - $currentDose
            );
        } elseif ($lastMno > $mnoTo) {
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

        $newDose = round($newDose * 4) / 4; // округление до 0.25

        $variants = [
            ['dose' => max(0, $newDose), 'label' => 'Основной'],
            ['dose' => max(0, $newDose - 0.25), 'label' => 'Сниженный'],
            ['dose' => $newDose + 0.25, 'label' => 'Повышенный'],
        ];

        return ['variants' => $variants, 'explanation' => $explanation];
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