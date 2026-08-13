<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Model;

/**
 * Получатель уведомления. Какие поля заполнены — определяет, через какие каналы
 * уведомление может быть доставлено.
 */
final class Recipient
{
    public function __construct(
        public readonly ?int $patientId = null,
        public readonly ?int $treatmentId = null,
        public readonly ?string $phone = null,
        public readonly ?string $email = null,
        public readonly ?string $pushToken = null,
        public readonly ?string $maxUserId = null,
    ) {
    }

    /**
     * Возвращает адрес/идентификатор получателя для конкретного канала (для лога и адаптеров).
     */
    public function addressFor(string $channelType): ?string
    {
        return match ($channelType) {
            'sms' => $this->phone,
            'email' => $this->email,
            'push' => $this->pushToken,
            'max' => $this->maxUserId,
            default => null,
        };
    }
}
