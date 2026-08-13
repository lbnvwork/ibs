<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\Communication\Entity;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\Communication\Entity\NotificationLog;
use Ibs\Context\Communication\Entity\NotificationTemplate;
use Ibs\Context\Communication\Entity\SmsIn;
use Ibs\Context\Communication\Entity\SmsOut;
use Ibs\Context\Communication\Entity\SmsOutStatus;
use Ibs\Context\Communication\Entity\SmsTemplate;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CommunicationPersistenceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        $connection = $this->entityManager->getConnection();
        if ($connection->isTransactionActive()) {
            $connection->rollBack();
        }

        $this->entityManager->close();

        parent::tearDown();
    }

    public function testSmsOutAndItsStatusArePersistedAndLinked(): void
    {
        $smsOut = new SmsOut();
        $smsOut->setSmsTarget('79001234567');
        $smsOut->setText('Не забудьте принять лекарство');
        $this->entityManager->persist($smsOut);

        $status = new SmsOutStatus();
        $status->setSmsOut($smsOut);
        $status->setStatus(1);
        $this->entityManager->persist($status);

        $this->entityManager->flush();
        $smsOutId = $smsOut->getId();
        $statusId = $status->getId();
        $this->entityManager->clear();

        $reloadedSms = $this->entityManager->find(SmsOut::class, $smsOutId);
        $reloadedStatus = $this->entityManager->find(SmsOutStatus::class, $statusId);

        $this->assertSame('79001234567', $reloadedSms->getSmsTarget());
        $this->assertSame($smsOutId, $reloadedStatus->getSmsOut()->getId());
    }

    public function testSmsInAndSmsTemplateArePersisted(): void
    {
        $smsIn = new SmsIn();
        $smsIn->setNum('79007654321');
        $smsIn->setText('СТОП');
        $this->entityManager->persist($smsIn);

        $template = new SmsTemplate();
        $template->setSmsType(1);
        $template->setSmsTemplate('Здравствуйте, {name}!');
        $this->entityManager->persist($template);

        $this->entityManager->flush();
        $smsInId = $smsIn->getId();
        $templateId = $template->getId();
        $this->entityManager->clear();

        $this->assertSame('СТОП', $this->entityManager->find(SmsIn::class, $smsInId)->getText());
        $this->assertSame('Здравствуйте, {name}!', $this->entityManager->find(SmsTemplate::class, $templateId)->getSmsTemplate());
    }

    public function testNotificationLogIsPersisted(): void
    {
        $log = (new NotificationLog())
            ->setPatientId(10)
            ->setTreatmentId(20)
            ->setChannelType('sms')
            ->setRecipientAddress('+70000000000')
            ->setPriority('routine')
            ->setTemplateCode('reminder_24h')
            ->setStatus('sent')
            ->setCreatedAt(new \DateTimeImmutable('2026-08-06 12:00:00'));

        $this->entityManager->persist($log);
        $this->entityManager->flush();
        $logId = $log->getId();
        $this->entityManager->clear();

        $reloaded = $this->entityManager->find(NotificationLog::class, $logId);
        $this->assertSame('sms', $reloaded->getChannelType());
        $this->assertSame('sent', $reloaded->getStatus());
        $this->assertSame(10, $reloaded->getPatientId());
        $this->assertSame('reminder_24h', $reloaded->getTemplateCode());
    }

    public function testNotificationTemplateIsPersisted(): void
    {
        $template = (new NotificationTemplate('reminder_24h', 'sms'))
            ->setBodyTemplate('Пора измерить МНО, %patient_name%.')
            ->setDescription('Напоминание через 24 часа после назначения');

        $this->entityManager->persist($template);
        $this->entityManager->flush();
        $templateId = $template->getId();
        $this->entityManager->clear();

        $reloaded = $this->entityManager->find(NotificationTemplate::class, $templateId);
        $this->assertSame('reminder_24h', $reloaded->getCode());
        $this->assertSame('sms', $reloaded->getChannel());
        $this->assertSame('Пора измерить МНО, %patient_name%.', $reloaded->getBodyTemplate());
    }

    public function testNotificationTemplateCodeAndChannelAreUnique(): void
    {
        $template1 = new NotificationTemplate('duplicate_code', 'sms');
        $template1->setBodyTemplate('first');
        $this->entityManager->persist($template1);
        $this->entityManager->flush();

        $this->expectException(UniqueConstraintViolationException::class);

        $template2 = new NotificationTemplate('duplicate_code', 'sms');
        $template2->setBodyTemplate('second');
        $this->entityManager->persist($template2);
        $this->entityManager->flush();
    }
}
