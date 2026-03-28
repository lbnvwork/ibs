<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\TestHistoryRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class TestHistoryLatestProvider implements ProviderInterface
{
    public function __construct(
        private TestHistoryRepository $repository,
        private RequestStack $requestStack
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $treatmentIds = $request->query->all('treatment');
        if (empty($treatmentIds)) {
            return [];
        }
        $treatmentIds = array_map('intval', $treatmentIds);
        return $this->repository->findLatestByTreatmentIds($treatmentIds);
    }
}