<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Model;

enum Priority: string
{
    /** Критические алерты — синхронная отправка, ошибка пробрасывается вызывающему коду. */
    case IMMEDIATE = 'immediate';

    /** Напоминания и информационные рассылки — через очередь, с fallback на синхронную отправку. */
    case ROUTINE = 'routine';
}
