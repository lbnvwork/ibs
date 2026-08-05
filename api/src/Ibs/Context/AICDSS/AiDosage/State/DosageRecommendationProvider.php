<?php

declare(strict_types=1);

namespace Ibs\Context\AICDSS\AiDosage\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Ibs\Context\AICDSS\AiDosage\Dto\DosageRecommendation;
use Ibs\Context\AICDSS\AiDosage\Service\DosageRecommendationEngine;
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