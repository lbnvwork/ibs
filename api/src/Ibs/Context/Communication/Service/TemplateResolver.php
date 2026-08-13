<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Service;

use Ibs\Context\Communication\Model\NotificationMessage;
use Ibs\Context\Communication\Repository\NotificationTemplateRepository;
use Ibs\Context\Communication\Service\Exception\TemplateNotFoundException;

/**
 * Разрешает шаблон уведомления по коду (NotificationTemplate) и подставляет
 * плейсхолдеры вида %key% значениями из NotificationMessage::$data. Если код
 * шаблона не указан, подстановка выполняется прямо в body/subject сообщения.
 */
final class TemplateResolver
{
    public function __construct(
        private readonly NotificationTemplateRepository $templates,
    ) {
    }

    public function resolve(NotificationMessage $message, string $channelType): NotificationMessage
    {
        $bodyTemplate = $message->body;
        $subjectTemplate = $message->subject;

        if (null !== $message->template) {
            $template = $this->templates->findOneByCodeAndChannel($message->template, $channelType);
            if (null === $template) {
                throw new TemplateNotFoundException($message->template, $channelType);
            }

            $bodyTemplate = $template->getBodyTemplate() ?? '';
            $subjectTemplate = $template->getSubjectTemplate() ?? $subjectTemplate;
        }

        return new NotificationMessage(
            body: $this->substitutePlaceholders($bodyTemplate, $message->data),
            subject: null !== $subjectTemplate ? $this->substitutePlaceholders($subjectTemplate, $message->data) : null,
            template: $message->template,
            data: $message->data,
            attachments: $message->attachments,
        );
    }

    /**
     * @param array<string, scalar|null> $data
     */
    private function substitutePlaceholders(string $text, array $data): string
    {
        $result = preg_replace_callback(
            '/%([a-zA-Z0-9_]+)%/',
            static function (array $matches) use ($data): string {
                // Оставляем плейсхолдер как есть, если данные для него не переданы.
                return \array_key_exists($matches[1], $data) ? (string) $data[$matches[1]] : $matches[0];
            },
            $text,
        );

        // preg_replace_callback() возвращает null при ошибке PCRE — не подставляем ничего, а не падаем с TypeError.
        return $result ?? $text;
    }
}
