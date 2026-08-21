<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\Communication\Service;

use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\Communication\Entity\MaxDeepLink;
use Ibs\Context\Communication\Entity\PatientChannelIdentity;
use Ibs\Context\Communication\Repository\MaxDeepLinkRepository;
use Ibs\Context\Communication\Repository\PatientChannelIdentityRepository;
use Ibs\Context\Communication\Service\MaxUpdateProcessor;
use PHPUnit\Framework\TestCase;

class MaxUpdateProcessorTest extends TestCase
{
    private function processor(
        PatientChannelIdentityRepository $identities,
        MaxDeepLinkRepository $deeplinks,
        EntityManagerInterface $entityManager,
    ): MaxUpdateProcessor {
        return new MaxUpdateProcessor($identities, $deeplinks, $entityManager);
    }

    public function testProcessSavesNewContactFromToken(): void
    {
        $identities = $this->createStub(PatientChannelIdentityRepository::class);
        $identities->method('findOneByPatientAndChannel')->willReturn(null);

        $deeplinks = $this->createStub(MaxDeepLinkRepository::class);
        $deeplinks->method('findByToken')->willReturn(new MaxDeepLink(999011, 'token-42'));

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('persist')
            ->with(self::callback(static function ($entity): bool {
                return $entity instanceof PatientChannelIdentity
                    && $entity->getPatientId() === 999011
                    && $entity->getChannelType() === 'max'
                    && $entity->getValue() === 'chat-42';
            }));

        $processor = $this->processor($identities, $deeplinks, $entityManager);
        $result = $processor->process(['chat_id' => 'chat-42', 'payload' => 'token-42']);

        self::assertSame(['patientId' => 999011, 'chatId' => 'chat-42'], $result);
    }

    public function testProcessSkipsUnknownToken(): void
    {
        $identities = $this->createStub(PatientChannelIdentityRepository::class);

        $deeplinks = $this->createStub(MaxDeepLinkRepository::class);
        $deeplinks->method('findByToken')->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('persist');

        $processor = $this->processor($identities, $deeplinks, $entityManager);

        self::assertNull($processor->process(['chat_id' => 'chat-42', 'payload' => 'unknown']));
    }

    public function testProcessSkipsMissingPayload(): void
    {
        $identities = $this->createStub(PatientChannelIdentityRepository::class);
        $deeplinks = $this->createStub(MaxDeepLinkRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('persist');

        $processor = $this->processor($identities, $deeplinks, $entityManager);

        self::assertNull($processor->process(['chat_id' => 'chat-42']));
    }

    public function testProcessUpdatesExistingContact(): void
    {
        $existing = (new PatientChannelIdentity())
            ->setPatientId(999011)
            ->setChannelType('max')
            ->setValue('old-chat');

        $identities = $this->createStub(PatientChannelIdentityRepository::class);
        $identities->method('findOneByPatientAndChannel')->willReturn($existing);

        $deeplinks = $this->createStub(MaxDeepLinkRepository::class);
        $deeplinks->method('findByToken')->willReturn(new MaxDeepLink(999011, 'token-42'));

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('persist');

        $processor = $this->processor($identities, $deeplinks, $entityManager);
        $result = $processor->process(['chat_id' => 'chat-42', 'payload' => 'token-42']);

        self::assertSame('chat-42', $existing->getValue());
        self::assertSame(['patientId' => 999011, 'chatId' => 'chat-42'], $result);
    }

    public function testProcessAcceptsIntChatId(): void
    {
        $identities = $this->createStub(PatientChannelIdentityRepository::class);
        $identities->method('findOneByPatientAndChannel')->willReturn(null);

        $deeplinks = $this->createStub(MaxDeepLinkRepository::class);
        $deeplinks->method('findByToken')->willReturn(new MaxDeepLink(999011, 'token-42'));

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist');

        $processor = $this->processor($identities, $deeplinks, $entityManager);
        $result = $processor->process(['chat_id' => 42342534, 'payload' => 'token-42']);

        self::assertSame(['patientId' => 999011, 'chatId' => '42342534'], $result);
    }
}

