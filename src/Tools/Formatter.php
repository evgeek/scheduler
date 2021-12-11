<?php

namespace Evgeek\Scheduler\Tools;

use Throwable;

class Formatter
{
    /**
     * Formatting Exceptions
     *
     * @param string $logExceptionFormat
     * @param Throwable $e
     * @return string
     */
    public static function exception(string $logExceptionFormat, Throwable $e): string
    {
        $placeholders = [
            '{{code}}',
            '{{message}}',
            '{{class}}',
            '{{stacktrace}}',
        ];
        $values = [
            $e->getCode(),
            $e->getMessage(),
            get_class($e),
            $e->getTraceAsString(),
        ];

        return str_replace($placeholders, $values, $logExceptionFormat);
    }
}