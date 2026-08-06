<?php

declare(strict_types=1);

namespace Ibs\Context\TreatmentTherapy\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Ibs\Context\TreatmentTherapy\Dto\PatientStatus\PatientStatusRequest;
use Ibs\Context\TreatmentTherapy\Dto\PatientStatus\PatientStatusResponse;
use Ibs\Context\TreatmentTherapy\Repository\TreatmentRepository;

final class PatientStatusProcessor implements ProcessorInterface
{
    private const ACTIVE_PATIENT_STATUS = 'активный';
    private const CANCELLED_PATIENT_STATUS = 'неактивный';

    public function __construct(
        private TreatmentRepository $treatmentRepository
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var PatientStatusRequest $data */
        $patientIds = $data->ids;

        $activeIds = $this->treatmentRepository->getActivePatientIds($patientIds);

        $result = [];
        foreach ($patientIds as $id) {
            $status = in_array($id, $activeIds) ? self::ACTIVE_PATIENT_STATUS : self::CANCELLED_PATIENT_STATUS;
            $result[] = new PatientStatusResponse($id, $status);
        }

        return $result;
    }
}