<?php

namespace Evgeek\Scheduler\Constant;

final class PhpErrors
{
    /** @var array Fatal PHP errors */
    public const FATAL = [
        E_ERROR => 'E_ERROR',
        E_PARSE => 'E_PARSE',
        E_CORE_ERROR => 'E_CORE_ERROR',
        E_COMPILE_ERROR => 'E_COMPILE_ERROR',
        E_USER_ERROR => 'E_USER_ERROR',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
    ];

    /**
     * Returns mask of all fatal PHP errors
     * @return int
     */
    public static function fatalMask(): int
    {
        return array_reduce(
            array_flip(self::FATAL),
            static function ($carry, $item) {
                $carry |= $item;
                return $carry;
            },
            0
        );
    }
}