<?php

namespace Evgeek\Scheduler\Tools;

use Throwable;

class ExceptionTools
{
    public static function format(Throwable $e): string
    {
        return '[code]: ' . $e->getCode() . PHP_EOL .
            '[message]: ' . $e->getMessage() . PHP_EOL .
            '[stacktrace]:' . PHP_EOL . $e->getTraceAsString();
    }
}