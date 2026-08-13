<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Model;

/**
 * Содержимое уведомления. `body`/`subject` могут содержать плейсхолдеры вида
 * %patient_name%, подставляемые из `data`. Если указан `template`, текст
 * берётся из соответствующего NotificationTemplate, а `body`/`subject`
 * игнорируются TemplateResolver'ом.
 */
final class NotificationMessage
{
    /**
     * @param array<string, scalar|null> $data
     * @param array<int, mixed>|null $attachments
     */
    public function __construct(
        public readonly string $body,
        public readonly ?string $subject = null,
        public readonly ?string $template = null,
        public readonly array $data = [],
        public readonly ?array $attachments = null,
    ) {
    }
}
