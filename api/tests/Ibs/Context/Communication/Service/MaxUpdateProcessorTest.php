<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\Communication\Service;

use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\Communication\Entity\PatientChannelIdentity;
use Ibs\Context\Communication\Repository\PatientChannelIdentityRepository;
use Ibs\Context\Communication\Service\MaxUpdateProcessor;
use PHPUnit\Framework\TestCase;

class MaxUpdateProcessorTest extends TestCase
{
    public function testProcessSavesNewContact(): void
    {
        $identities = $this->createStub(PatientChannelIdentityRepository::class);
        $identities->method('findOneByPatientAndChannel')->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('persist')
            ->with(self::callback(static function ($entity): bool {
                if (!$entity instanceof PatientChannelIdentity) {
                    return false;
                }

                return $entity->getPatientId() === 999011
                    && $entity->getChannelType() === 'max'
                    && $entity->getValue() === 'chat-42';
            }));

        $processor = new MaxUpdateProcessor($identities, $entityManager);

        $result = $processor->process(['chat_id' => 'chat-42', 'payload' => '999011']);

        self::assertSame(['patientId' => 999011, 'chatId' => 'chat-42'], $result);
    }

    public function testProcessSkipsNonNumericPayload(): void
    {
        $identities = $this->createStub(PatientChannelIdentityRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('persist');

        $processor = new MaxUpdateProcessor($identities, $entityManager);

        self::assertNull($processor->process(['chat_id' => 'chat-42', 'payload' => 'abc']));
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

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('persist');

        $processor = new MaxUpdateProcessor($identities, $entityManager);

        $result = $processor->process(['chat_id' => 'chat-42', 'payload' => '999011']);

        self::assertSame('chat-42', $existing->getValue());
        self::assertSame(['patientId' => 999011, 'chatId' => 'chat-42'], $result);
    }

    public function testProcessAcceptsIntChatId(): void
    {
        $identities = $this->createStub(PatientChannelIdentityRepository::class);
        $identities->method('findOneByPatientAndChannel')->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist');

        $processor = new MaxUpdateProcessor($identities, $entityManager);

        $result = $processor->process(['chat_id' => 42342534, 'payload' => '999011']);

        self::assertSame(['patientId' => 999011, 'chatId' => '42342534'], $result);
    }
}
