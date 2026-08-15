<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\Communication\Service;

use Ibs\Context\Communication\Entity\PatientChannelIdentity;
use Ibs\Context\Communication\Repository\PatientChannelIdentityRepository;
use Ibs\Context\Communication\Service\PatientContactResolver;
use PHPUnit\Framework\TestCase;

class PatientContactResolverTest extends TestCase
{
    public function testReturnsValueForExistingPatientAndChannel(): void
    {
        $identity = (new PatientChannelIdentity())
            ->setPatientId(42)
            ->setChannelType('max')
            ->setValue('chat-42');

        $repository = $this->createMock(PatientChannelIdentityRepository::class);
        $repository->expects(self::once())
            ->method('findOneByPatientAndChannel')
            ->with(42, 'max')
            ->willReturn($identity);

        $resolver = new PatientContactResolver($repository);

        self::assertSame('chat-42', $resolver->get(42, 'max'));
    }

    public function testReturnsNullWhenNoIdentity(): void
    {
        $repository = $this->createStub(PatientChannelIdentityRepository::class);
        $repository->method('findOneByPatientAndChannel')->willReturn(null);

        $resolver = new PatientContactResolver($repository);

        self::assertNull($resolver->get(42, 'sms'));
    }

    public function testReturnsNullWhenPatientIdIsNull(): void
    {
        $repository = $this->createMock(PatientChannelIdentityRepository::class);
        $repository->expects(self::never())
            ->method('findOneByPatientAndChannel');

        $resolver = new PatientContactResolver($repository);

        self::assertNull($resolver->get(null, 'max'));
    }

    public function testReturnsNullWhenPatientIdIsZero(): void
    {
        $repository = $this->createMock(PatientChannelIdentityRepository::class);
        $repository->expects(self::never())
            ->method('findOneByPatientAndChannel');

        $resolver = new PatientContactResolver($repository);

        self::assertNull($resolver->get(0, 'max'));
    }

    public function testReturnsNullForUnknownChannel(): void
    {
        $repository = $this->createMock(PatientChannelIdentityRepository::class);
        $repository->expects(self::once())
            ->method('findOneByPatientAndChannel')
            ->with(42, 'unknown_channel')
            ->willReturn(null);

        $resolver = new PatientContactResolver($repository);

        self::assertNull($resolver->get(42, 'unknown_channel'));
    }
}