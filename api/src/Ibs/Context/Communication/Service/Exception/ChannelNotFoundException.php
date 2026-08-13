<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Service\Exception;

/**
 * Выбрасывается при запросе канала, для которого не зарегистрирован сервис с тегом
 * 'communication.channel'.
 */
class ChannelNotFoundException extends \RuntimeException
{
    public function __construct(string $channelType)
    {
        parent::__construct(\sprintf('Notification channel "%s" is not registered.', $channelType));
    }
}
