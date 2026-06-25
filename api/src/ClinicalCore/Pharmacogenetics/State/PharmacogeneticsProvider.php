<?php

declare(strict_types=1);

namespace App\ClinicalCore\Pharmacogenetics\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ClinicalCore\Pharmacogenetics\Dto\PharmacogeneticsResponse;
use App\ClinicalCore\Pharmacogenetics\Service\PharmacogeneticsService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PharmacogeneticsProvider implements ProviderInterface
{
    public function __construct(
        private PharmacogeneticsService $service
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): PharmacogeneticsResponse
    {
        $patientId = (int) $uriVariables['patientId'];

        // Извлекаем drug IRI из query-параметров
        $request = $context['request'] ?? null;
        $drugId = null;

        if ($request instanceof Request) {
            $drugIri = $request->query->get('drug');
            if ($drugIri !== null) {
                $drugId = $this->extractIdFromIri($drugIri);
                if ($drugId === null) {
                    throw new BadRequestHttpException('Неверный формат IRI для параметра drug.');
                }
            }
        }

        $response = new PharmacogeneticsResponse();
        $response->markers = $this->service->getPatientPharmacogenetics($patientId, $drugId);

        return $response;
    }

    /**
     * Извлекает числовой ID из IRI вида /api/drugs/123.
     *
     * @param string $iri
     * @return int|null
     */
    private function extractIdFromIri(string $iri): ?int
    {
        if (preg_match('~/(\d+)$~', $iri, $matches)) {
            return (int) $matches[1];
        }
        return null;
    }
}