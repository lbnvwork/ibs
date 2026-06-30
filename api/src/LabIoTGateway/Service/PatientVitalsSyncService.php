<?php

declare(strict_types=1);

namespace App\LabIoTGateway\Service;

use App\Entity\Patient;
use App\LabIoTGateway\Entity\PatientVitals;
use App\LabIoTGateway\Entity\PatientVitalsLatest;
use Doctrine\ORM\EntityManagerInterface;

class PatientVitalsSyncService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function syncFromVitals(PatientVitals $vitals): void
    {
        $patientId = $vitals->getPatient()->getId();
        $latest = $this->entityManager->find(PatientVitalsLatest::class, $patientId);

        if ($latest === null) {
            $patient = $this->entityManager->getReference(Patient::class, $patientId);
            $latest = new PatientVitalsLatest($patient);
            $this->entityManager->persist($latest);
        }

        // Обновляем только не-null поля
        if ($vitals->getHb() !== null) {
            $latest->setHb($vitals->getHb());
        }
        if ($vitals->getHeartRate() !== null) {
            $latest->setHeartRate($vitals->getHeartRate());
        }
        if ($vitals->getSystolicPressure() !== null) {
            $latest->setSystolicPressure($vitals->getSystolicPressure());
        }
        if ($vitals->getDiastolicPressure() !== null) {
            $latest->setDiastolicPressure($vitals->getDiastolicPressure());
        }
        if ($vitals->getSaturation() !== null) {
            $latest->setSaturation($vitals->getSaturation());
        }
        if ($vitals->getWeight() !== null) {
            $latest->setWeight($vitals->getWeight());
        }

        $latest->setLastUpdated(new \DateTime());
        $this->entityManager->flush();
    }
}