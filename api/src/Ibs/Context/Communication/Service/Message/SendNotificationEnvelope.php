<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Service\Message;

use Ibs\Context\Communication\Model\NotificationMessage;
use Ibs\Context\Communication\Model\Priority;
use Ibs\Context\Communication\Model\Recipient;

/**
 * Сообщение Messenger для асинхронной (ROUTINE) доставки уведомления.
 * Обрабатывается NotificationService::handleEnvelope().
 */
final class SendNotificationEnvelope
{
    /**
     * @param string[] $channels
     */
    public function __construct(
        public readonly Recipient $recipient,
        public readonly NotificationMessage $message,
        public readonly array $channels,
        public readonly Priority $priority,
    ) {
    }
}
