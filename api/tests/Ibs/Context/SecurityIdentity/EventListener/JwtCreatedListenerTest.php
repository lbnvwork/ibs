<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\SecurityIdentity\EventListener;

use Ibs\Context\SecurityIdentity\Entity\User;
use Ibs\Context\SecurityIdentity\EventListener\JwtCreatedListener;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use PHPUnit\Framework\TestCase;

class JwtCreatedListenerTest extends TestCase
{
    public function testUserIdIsAddedToJwtPayload(): void
    {
        $user = new User();
        $user->setLogin('jwt.test.user');

        $reflection = new \ReflectionProperty(User::class, 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($user, 42);

        $event = new JWTCreatedEvent(['sub' => 'jwt.test.user'], $user);

        (new JwtCreatedListener())->onJWTCreated($event);

        $this->assertSame(42, $event->getData()['id']);
    }
}
