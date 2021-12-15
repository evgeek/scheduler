<?php

namespace Evgeek\Scheduler\Tools;

use Throwable;

class Formatter
{
    /**
     * Formatting Exceptions
     *
     * @param string $format
     * @param int|null $msgLengthLimit
     * @param string $header
     * @param Throwable $e
     * @return string
     */
    public static function exception(string $format, ?int $msgLengthLimit, string $header, Throwable $e): string
    {
        $message = $msgLengthLimit === null ?
            $e->getMessage() :
            static::truncateString($e->getMessage(), $msgLengthLimit);

        $placeholders = [
            '{{header}}',
            '{{code}}',
            '{{class}}',
            '{{message}}',
            '{{stacktrace}}',
        ];
        $values = [
            $header,
            $e->getCode(),
            get_class($e),
            $message,
            $e->getTraceAsString(),
        ];

        return str_replace($placeholders, $values, $format);
    }

    /**
     * Formatting log messages
     *
     * @param string $format
     * @param int|null $msgLengthLimit
     * @param int|null $taskId
     * @param string $taskType
     * @param string $name
     * @param string $message
     * @param string $description
     * @return string
     */
    public static function logMessage(
        string $format,
        ?int   $msgLengthLimit,
        ?int   $taskId,
        string $taskType,
        string $name,
        string $message,
        string $description
    ): string
    {
        $placeholders = [
            '{{task_id}}',
            '{{TASK_ID}}',
            '{{task_type}}',
            '{{TASK_TYPE}}',
            '{{task_name}}',
            '{{TASK_NAME}}',
            '{{message}}',
            '{{MESSAGE}}',
            '{{task_description}}',
            '{{TASK_DESCRIPTION}}'
        ];
        $values = [
            $taskId,
            $taskId,
            $taskType,
            strtoupper($taskType),
            $name,
            strtoupper($name),
            $message,
            strtoupper($message),
            $description,
            strtoupper($description)
        ];

        /** @var string $message */
        $message = str_replace($placeholders, $values, $format);

        return $msgLengthLimit === null ?
            $message :
            static::truncateString($message, $msgLengthLimit);
    }

    /**
     * Truncate string to passed length
     *
     * @param string $string
     * @param int $length
     * @return string
     */
    public static function truncateString(string $string, int $length): string
    {
        $stub = ' (...truncated)';
        if (strlen($string) > $length) {
            return substr($string, 0, $length - strlen($stub)) . $stub;
        }

        return $string;
    }
}