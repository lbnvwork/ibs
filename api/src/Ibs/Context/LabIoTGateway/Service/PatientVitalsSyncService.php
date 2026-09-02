<?php

declare(strict_types=1);

namespace Ibs\Context\LabIoTGateway\Service;

use Ibs\Context\LabIoTGateway\Entity\PatientVitals;
use Ibs\Context\LabIoTGateway\Entity\PatientVitalsLatest;
use Doctrine\ORM\EntityManagerInterface;

class PatientVitalsSyncService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function syncFromVitals(PatientVitals $vitals): void
    {
        $patient = $vitals->getPatient();
        if ($patient === null) {
            return;
        }

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
        if ($vitals->getCreatinine() !== null) {
            $latest->setCreatinine($vitals->getCreatinine());
        }

        $latest->setLastUpdated(new \DateTime());
        $this->entityManager->flush();
    }
}