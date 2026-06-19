<?php

declare(strict_types=1);

namespace App\ClinicalCore\Pharmacogenetics\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ClinicalCore\Pharmacogenetics\Dto\PharmacogeneticsResponse;
use App\ClinicalCore\Pharmacogenetics\Service\PharmacogeneticsService;

class PharmacogeneticsProvider implements ProviderInterface
{
    public function __construct(
        private PharmacogeneticsService $service
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): PharmacogeneticsResponse
    {
        $patientId = (int) $uriVariables['patientId'];

        $response = new PharmacogeneticsResponse();
        $response->markers = $this->service->getPatientPharmacogenetics($patientId);

        return $response;
    }
}