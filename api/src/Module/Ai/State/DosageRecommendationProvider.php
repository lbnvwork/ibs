<?php

declare(strict_types=1);

namespace App\Module\Ai\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Module\Ai\Dto\DosageRecommendation;
use App\Module\Ai\Service\DosageRecommendationEngine;
use Symfony\Component\HttpFoundation\RequestStack;

class DosageRecommendationProvider implements ProviderInterface
{
    public function __construct(
        private DosageRecommendationEngine $engine,
        private RequestStack $requestStack
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): DosageRecommendation
    {
        $request = $this->requestStack->getCurrentRequest();
        $treatmentId = (int) $request->query->get('treatment_id');

        $result = $this->engine->recommend($treatmentId);

        $dto = new DosageRecommendation();
        $dto->variants = $result['variants'];
        $dto->explanation = $result['explanation'];

        return $dto;
    }
}