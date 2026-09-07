<?php

declare(strict_types=1);

namespace Ibs\Context\TreatmentTherapy\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Ibs\Context\TreatmentTherapy\Entity\TestHistory;
use Ibs\Context\TreatmentTherapy\Repository\TestHistoryRepository;
use Symfony\Component\HttpFoundation\RequestStack;

/** @implements ProviderInterface<TestHistory> */
class TestHistoryLatestProvider implements ProviderInterface
{
    public function __construct(
        private TestHistoryRepository $repository,
        private RequestStack $requestStack
    ) {}

    /** @return list<TestHistory> */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return [];
        }
        $treatmentIds = $request->query->all('treatment');
        if (empty($treatmentIds)) {
            return [];
        }
        $treatmentIds = array_map(static fn (mixed $id): int => (int) (is_scalar($id) ? $id : 0), $treatmentIds);
        return $this->repository->findLatestByTreatmentIds($treatmentIds);
    }
}