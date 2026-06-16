<?php

declare(strict_types=1);

namespace App\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\State\PharmacogeneticsProvider;

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