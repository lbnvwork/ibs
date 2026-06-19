<?php

declare(strict_types=1);

namespace App\ClinicalCore\Pharmacogenetics\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\ClinicalCore\Pharmacogenetics\State\PharmacogeneticsProvider;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/patients/{patientId}/pharmacogenetics',
            provider: PharmacogeneticsProvider::class
        )
    ]
)]
class PharmacogeneticsResponse
{
    public array $markers = [];
}