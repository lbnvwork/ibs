<?php

declare(strict_types=1);

namespace App\LabIoTGateway\Service;

use App\Entity\PatientVitals;
use App\Entity\PatientVitalsLatest;
use Doctrine\ORM\EntityManagerInterface;

class PatientVitalsSyncService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function syncFromVitals(PatientVitals $vitals): void
    {
        $patient = $vitals->getPatient();
        $latest = $this->entityManager->getRepository(PatientVitalsLatest::class)
            ->findOneBy(['patient' => $patient]);

        if ($latest === null) {
            $latest = new PatientVitalsLatest($patient);
            $this->entityManager->persist($latest);
        }

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