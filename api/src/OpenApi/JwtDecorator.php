<?php

declare(strict_types=1);

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use ApiPlatform\OpenApi\Model\SecurityScheme;

final readonly class JwtDecorator implements OpenApiFactoryInterface
{
    public function __construct(
        private OpenApiFactoryInterface $decorated
    ) {}

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $components = $openApi->getComponents();

        $securitySchemes = $components->getSecuritySchemes() ?: [];

        $securitySchemes['JWT'] = new SecurityScheme(
            type: 'http',
            description: 'Введите JWT токен (без слова Bearer)',
            scheme: 'bearer',
            bearerFormat: 'JWT'
        );

        $components = $components->withSecuritySchemes($securitySchemes);
        $openApi = $openApi->withComponents($components);

        return $openApi->withSecurity([['JWT' => []]]);
    }
}