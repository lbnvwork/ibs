<?php

declare(strict_types=1);

namespace Ibs\Context\TreatmentTherapy\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Ibs\Context\TreatmentTherapy\Repository\Mkb10Repository;

class Mkb10PopularProvider implements ProviderInterface
{
    public function __construct(private Mkb10Repository $repository)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return $this->repository->findPopularActiveDiagnoses(10);
    }
}