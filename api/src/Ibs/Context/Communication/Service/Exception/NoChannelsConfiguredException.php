<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Service\Exception;

/**
 * Выбрасывается, если NotificationService::send() вызван без указания каналов доставки.
 */
class NoChannelsConfiguredException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('No notification channels were configured for this message.');
    }
}
