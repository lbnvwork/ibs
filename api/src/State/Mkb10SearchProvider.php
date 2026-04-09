<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\Mkb10Repository;
use Symfony\Component\HttpFoundation\RequestStack;

class Mkb10SearchProvider implements ProviderInterface
{
    public function __construct(
        private Mkb10Repository $repository,
        private RequestStack $requestStack
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $query = $request->query->get('q', '');
        if (strlen($query) < 2) {
            return [];
        }
        
        return $this->repository->searchByCodeOrName($query);
    }
}