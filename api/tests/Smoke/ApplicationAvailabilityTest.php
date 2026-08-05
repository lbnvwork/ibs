<?php

declare(strict_types=1);

namespace App\Tests\Smoke;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApplicationAvailabilityTest extends WebTestCase
{
    public function testApiDocsReturnsSuccessfulStatus(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/docs.json');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('application/json', (string) $client->getResponse()->headers->get('Content-Type'));
    }

    public function testUnauthenticatedApiRequestReturnsUnauthorized(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/hospitals');

        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testUnknownRouteReturnsNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/this-route-does-not-exist');

        $this->assertSame(404, $client->getResponse()->getStatusCode());
    }
}
