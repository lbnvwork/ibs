<?php

namespace App\Controller;

use App\Repository\TestHistoryRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class GetLatestTestHistoriesController
{
    public function __invoke(Request $request, TestHistoryRepository $repository): JsonResponse
    {
        $treatmentIds = $request->query->all('treatment');
        if (empty($treatmentIds)) {
            return new JsonResponse([]);
        }

        $treatmentIds = array_map('intval', $treatmentIds);
        $result = $repository->findLatestByTreatmentIds($treatmentIds);

        return new JsonResponse([
            '@context' => '/api/contexts/TestHistory',
            '@id' => '/api/test_histories/latest',
            '@type' => 'Collection',
            'member' => $result,
        ]);
    }
}