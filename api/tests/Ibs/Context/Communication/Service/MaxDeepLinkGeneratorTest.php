<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\Communication\Service;

use Doctrine\DBAL\Driver\Exception as DriverExceptionInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\Communication\Entity\MaxDeepLink;
use Ibs\Context\Communication\Repository\MaxDeepLinkRepository;
use Ibs\Context\Communication\Service\MaxDeepLinkGenerator;
use PHPUnit\Framework\TestCase;

class MaxDeepLinkGeneratorTest extends TestCase
{
    public function testForPatientGeneratesUrlAndPersists(): void
    {
        $deeplinks = $this->createStub(MaxDeepLinkRepository::class);
        $deeplinks->method('findByPatientId')->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('persist')
            ->with(self::callback(static function ($entity): bool {
                return $entity instanceof MaxDeepLink
                    && $entity->getPatientId() === 999011
                    && '' !== $entity->getToken();
            }));
        $entityManager->expects(self::once())->method('flush');

        $generator = new MaxDeepLinkGenerator($deeplinks, $entityManager, 'id463246156997_bot');
        $url = $generator->forPatient(999011);

        self::assertMatchesRegularExpression('#^https://max\.ru/id463246156997_bot\?start=[0-9a-f]{32}$#', $url);
    }

    public function testForPatientReusesExistingToken(): void
    {
        $deeplinks = $this->createStub(MaxDeepLinkRepository::class);
        $deeplinks->method('findByPatientId')->willReturn(new MaxDeepLink(999011, 'existing-token'));

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('persist');

        $generator = new MaxDeepLinkGenerator($deeplinks, $entityManager, 'id463246156997_bot');
        $url = $generator->forPatient(999011);

        self::assertSame('https://max.ru/id463246156997_bot?start=existing-token', $url);
    }

    public function testForPatientHandlesUniqueConstraintRace(): void
    {
        $existing = new MaxDeepLink(999011, 'raced-token');

        $deeplinks = $this->createMock(MaxDeepLinkRepository::class);
        $deeplinks->expects(self::exactly(2))
            ->method('findByPatientId')
            ->willReturnOnConsecutiveCalls(null, $existing);

        $driverException = new class('duplicate key value') extends \Exception implements DriverExceptionInterface {
            public function getSQLState(): ?string
            {
                return '23505';
            }
        };

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist');
        $entityManager->expects(self::once())
            ->method('flush')
            ->willThrowException(new UniqueConstraintViolationException($driverException, null));
        $entityManager->expects(self::once())->method('clear');

        $generator = new MaxDeepLinkGenerator($deeplinks, $entityManager, 'id463246156997_bot');
        $url = $generator->forPatient(999011);

        self::assertSame('https://max.ru/id463246156997_bot?start=raced-token', $url);
    }
}
