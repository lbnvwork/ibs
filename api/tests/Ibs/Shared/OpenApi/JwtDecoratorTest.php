<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Shared\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use Ibs\Shared\OpenApi\JwtDecorator;
use PHPUnit\Framework\TestCase;

class JwtDecoratorTest extends TestCase
{
    public function testAddsJwtBearerSecuritySchemeAndAppliesItGlobally(): void
    {
        $baseOpenApi = new OpenApi(
            new Info('Test API', '1.0.0'),
            [],
            new Paths(),
            new Components(securitySchemes: new \ArrayObject())
        );

        $innerFactory = new class($baseOpenApi) implements OpenApiFactoryInterface {
            public function __construct(private readonly OpenApi $openApi)
            {
            }

            public function __invoke(array $context = []): OpenApi
            {
                return $this->openApi;
            }
        };

        $decorated = (new JwtDecorator($innerFactory))();

        $securitySchemes = $decorated->getComponents()->getSecuritySchemes();
        $this->assertNotNull($securitySchemes);
        $this->assertArrayHasKey('JWT', (array) $securitySchemes);
        $this->assertSame('bearer', $securitySchemes['JWT']->getScheme());
        $this->assertSame([['JWT' => []]], $decorated->getSecurity());
    }
}
