<?php

declare(strict_types=1);

namespace Ibs\Context\AICDSS\AiDosage\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Ibs\Context\AICDSS\AiDosage\Dto\DosageRecommendation;
use Ibs\Context\AICDSS\AiDosage\Service\DosageRecommendationEngine;
use Symfony\Component\HttpFoundation\RequestStack;

/** @implements ProviderInterface<DosageRecommendation> */
class DosageRecommendationProvider implements ProviderInterface
{
    public function __construct(
        private DosageRecommendationEngine $engine,
        private RequestStack $requestStack
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): DosageRecommendation
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException('Нет активного запроса.');
        }
        $treatmentRaw = $request->query->get('treatment_id');
        $treatmentId = (int) (is_scalar($treatmentRaw) ? $treatmentRaw : 0);

        $result = $this->engine->recommend($treatmentId);

        $dto = new DosageRecommendation();
        $dto->variants = $result['variants'];
        $dto->explanation = $result['explanation'];

        return $dto;
    }
}