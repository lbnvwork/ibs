<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Service;

/**
 * Абстракция ожидания перед повторной попыткой доставки.
 * Позволяет NotificationService применять экспоненциальную задержку,
 * а тестам — проверять retry-логику без реальных sleep-вызовов.
 */
interface RetrySleeperInterface
{
    /**
     * Ждёт перед следующей попыткой.
     *
     * @param int $retryNumber номер уже выполненной неудачной retry-попытки:
     *                         0 — перед 1-м повтором,
     *                         1 — перед 2-м повтором,
     *                         2 — перед 3-м повтором.
     */
    public function wait(int $retryNumber): void;
}