<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\PharmacogeneticsResponse;
use App\Service\PharmacogeneticsService;

class PharmacogeneticsProvider implements ProviderInterface
{
    public function __construct(
        private PharmacogeneticsService $service
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): PharmacogeneticsResponse
    {
        $patientId = (int) $uriVariables['patientId'];
        $markers = $this->service->getPatientPharmacogenetics($patientId);

        $response = new PharmacogeneticsResponse();
        $response->markers = $markers;

        return $response;
    }
}