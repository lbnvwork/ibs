<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Service;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Реестр всех зарегистрированных каналов связи, проиндексированных по типу
 * канала. Собирает все сервисы с тегом 'communication.channel' — новый канал
 * достаточно зарегистрировать как сервис, реализующий ChannelInterface.
 */
final class ChannelRegistry
{
    /** @var array<string, ChannelInterface> */
    private readonly array $channelsByType;

    /**
     * @param iterable<ChannelInterface> $channels
     */
    public function __construct(
        #[AutowireIterator('communication.channel')]
        iterable $channels = [],
    ) {
        $channelsByType = [];
        foreach ($channels as $channel) {
            $channelsByType[$channel->getChannelType()] = $channel;
        }
        $this->channelsByType = $channelsByType;
    }

    public function get(string $channelType): ?ChannelInterface
    {
        return $this->channelsByType[$channelType] ?? null;
    }

    public function has(string $channelType): bool
    {
        return isset($this->channelsByType[$channelType]);
    }
}
