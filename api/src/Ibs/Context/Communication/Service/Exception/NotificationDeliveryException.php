<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Service\Exception;

/**
 * Выбрасывается для IMMEDIATE-приоритета, когда канал не смог доставить сообщение —
 * ошибка пробрасывается вызывающему коду.
 */
class NotificationDeliveryException extends \RuntimeException
{
    public function __construct(string $channelType, string $reason)
    {
        parent::__construct(\sprintf('Failed to deliver notification via channel "%s": %s', $channelType, $reason));
    }
}
