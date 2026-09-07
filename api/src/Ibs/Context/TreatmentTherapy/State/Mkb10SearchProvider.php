<?php

declare(strict_types=1);

namespace Ibs\Context\TreatmentTherapy\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Ibs\Context\TreatmentTherapy\Entity\Mkb10;
use Ibs\Context\TreatmentTherapy\Repository\Mkb10Repository;
use Symfony\Component\HttpFoundation\RequestStack;

/** @implements ProviderInterface<Mkb10> */
class Mkb10SearchProvider implements ProviderInterface
{
    public function __construct(
        private Mkb10Repository $repository,
        private RequestStack $requestStack
    ) {}

    /** @return list<Mkb10> */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return [];
        }
        $query = $request->query->get('q', '');
        if (strlen($query) < 2) {
            return [];
        }
        
        return $this->repository->searchByCodeOrName($query);
    }
}