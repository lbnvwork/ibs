<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Service;

use Ibs\Context\Communication\Model\NotificationMessage;
use Ibs\Context\Communication\Model\Recipient;
use Ibs\Context\Communication\Model\SendResult;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Контракт канала связи. Реализации регистрируются как сервисы с тегом
 * 'communication.channel' (автоматически, благодаря AutoconfigureTag на этом
 * интерфейсе) и подхватываются NotificationService через ChannelRegistry —
 * добавление нового канала не требует изменений в вызывающем коде.
 */
#[AutoconfigureTag('communication.channel')]
interface ChannelInterface
{
    public function send(Recipient $recipient, string $address, NotificationMessage $message): SendResult;

    public function isAvailable(): bool;

    /**
     * @return string например 'sms', 'push', 'email', 'max'
     */
    public function getChannelType(): string;
}
