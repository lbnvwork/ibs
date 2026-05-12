<?php

declare(strict_types=1);

namespace App\Module\Ai\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Module\Ai\State\DosageRecommendationProvider;

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