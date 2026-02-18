<?php

declare(strict_types=1);

namespace App\Resource;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use App\DTO\PatientStatus\PatientStatusResponse;
use App\DTO\PatientStatus\PatientStatusRequest;
use App\State\PatientStatusProcessor;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/patients/status',
            status: 200,
            openapi: new OpenApiOperation(
                tags: ['Пациенты'],
                responses: [
                    '200' => new Response(
                        description: 'Успешный ответ',
                        content: new \ArrayObject([
                            'application/json' => [
                                'schema' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'id' => ['type' => 'integer'],
                                            'status' => ['type' => 'string', 'enum' => ['активный', 'неактивный']]
                                        ]
                                    ]
                                ]
                            ]
                        ])
                    ),
                    '400' => new Response(description: 'Неверный запрос (например, не передан ids)'),
                    '422' => new Response(description: 'Ошибка валидации (некорректные ID)')
                ],
                summary: 'Получить статусы пациентов',
                description: 'Возвращает статусы (активный/неактивный) для переданных ID пациентов. Не изменяет состояние системы.',
                requestBody: new RequestBody(
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'ids' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'integer'],
                                        'example' => [1, 2, 3],
                                        'description' => 'Массив ID пациентов'
                                    ]
                                ],
                                'required' => ['ids']
                            ]
                        ]
                    ])
                )
            ),
            input: PatientStatusRequest::class,
            output: PatientStatusResponse::class,
            processor: PatientStatusProcessor::class
        )
    ]
)]
class PatientStatusResource
{
}