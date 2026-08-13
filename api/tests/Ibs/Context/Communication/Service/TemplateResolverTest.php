<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\Communication\Service;

use Ibs\Context\Communication\Entity\NotificationTemplate;
use Ibs\Context\Communication\Model\NotificationMessage;
use Ibs\Context\Communication\Repository\NotificationTemplateRepository;
use Ibs\Context\Communication\Service\Exception\TemplateNotFoundException;
use Ibs\Context\Communication\Service\TemplateResolver;
use PHPUnit\Framework\TestCase;

class TemplateResolverTest extends TestCase
{
    private function repositoryReturning(?NotificationTemplate $template): NotificationTemplateRepository
    {
        $repository = $this->createStub(NotificationTemplateRepository::class);
        $repository->method('findOneByCodeAndChannel')->willReturn($template);

        return $repository;
    }

    public function testSubstitutesPlaceholdersInPlainMessageWithoutTemplate(): void
    {
        $resolver = new TemplateResolver($this->repositoryReturning(null));

        $message = new NotificationMessage(
            body: 'Здравствуйте, %patient_name%! Ваш визит %visit_date%.',
            subject: 'Напоминание для %patient_name%',
            data: ['patient_name' => 'Иван', 'visit_date' => '10.08.2026'],
        );

        $resolved = $resolver->resolve($message, 'sms');

        self::assertSame('Здравствуйте, Иван! Ваш визит 10.08.2026.', $resolved->body);
        self::assertSame('Напоминание для Иван', $resolved->subject);
    }

    public function testLeavesUnknownPlaceholdersIntact(): void
    {
        $resolver = new TemplateResolver($this->repositoryReturning(null));

        $message = new NotificationMessage(body: 'Здравствуйте, %patient_name%! Код: %unknown_var%.', data: ['patient_name' => 'Иван']);

        $resolved = $resolver->resolve($message, 'sms');

        self::assertSame('Здравствуйте, Иван! Код: %unknown_var%.', $resolved->body);
    }

    public function testResolvesBodyAndSubjectFromStoredTemplate(): void
    {
        $template = (new NotificationTemplate('reminder_24h', 'email'))
            ->setSubjectTemplate('Напоминание, %patient_name%')
            ->setBodyTemplate('Просьба измерить МНО, %patient_name%.');

        $resolver = new TemplateResolver($this->repositoryReturning($template));

        $message = new NotificationMessage(body: 'ignored', subject: 'ignored', template: 'reminder_24h', data: ['patient_name' => 'Мария']);

        $resolved = $resolver->resolve($message, 'email');

        self::assertSame('Просьба измерить МНО, Мария.', $resolved->body);
        self::assertSame('Напоминание, Мария', $resolved->subject);
        self::assertSame('reminder_24h', $resolved->template);
    }

    public function testFallsBackToMessageSubjectWhenTemplateHasNoSubject(): void
    {
        $template = (new NotificationTemplate('reminder_24h', 'sms'))
            ->setBodyTemplate('Текст из шаблона');

        $resolver = new TemplateResolver($this->repositoryReturning($template));

        $message = new NotificationMessage(body: 'ignored', subject: 'Исходная тема', template: 'reminder_24h');

        $resolved = $resolver->resolve($message, 'sms');

        self::assertSame('Исходная тема', $resolved->subject);
    }

    public function testThrowsWhenTemplateCodeNotFound(): void
    {
        $resolver = new TemplateResolver($this->repositoryReturning(null));

        $message = new NotificationMessage(body: 'ignored', template: 'missing_code');

        $this->expectException(TemplateNotFoundException::class);

        $resolver->resolve($message, 'sms');
    }

    public function testPreservesAttachmentsAndDataOnResolvedMessage(): void
    {
        $resolver = new TemplateResolver($this->repositoryReturning(null));

        $attachments = [['name' => 'file.pdf']];
        $message = new NotificationMessage(body: 'text', data: ['a' => 1], attachments: $attachments);

        $resolved = $resolver->resolve($message, 'email');

        self::assertSame($attachments, $resolved->attachments);
        self::assertSame(['a' => 1], $resolved->data);
    }
}
