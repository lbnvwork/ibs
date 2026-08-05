<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\Communication\Entity;

use Doctrine\ORM\EntityManagerInterface;
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
}
