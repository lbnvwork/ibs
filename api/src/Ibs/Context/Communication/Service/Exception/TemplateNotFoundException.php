<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Service\Exception;

/**
 * Выбрасывается TemplateResolver'ом, если для указанного кода и канала не найден NotificationTemplate.
 */
class TemplateNotFoundException extends \RuntimeException
{
    public function __construct(string $templateCode, string $channelType)
    {
        parent::__construct(\sprintf('Notification template "%s" for channel "%s" was not found.', $templateCode, $channelType));
    }
}
