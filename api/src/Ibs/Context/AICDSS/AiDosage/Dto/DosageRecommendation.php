<?php

declare(strict_types=1);

namespace Ibs\Context\AICDSS\AiDosage\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Ibs\Context\AICDSS\AiDosage\State\DosageRecommendationProvider;

#[ApiResource(
    shortName: 'Dosage',
    operations: [
        new Get(
            uriTemplate: '/dosage/recommendation',
            provider: DosageRecommendationProvider::class
        )
    ]
)]
class DosageRecommendation
{
    public array $variants = [];
    public string $explanation = '';
}