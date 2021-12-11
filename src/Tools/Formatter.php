<?php

namespace Evgeek\Scheduler\Tools;

use Throwable;

class Formatter
{
    /**
     * Formatting Exceptions
     *
     * @param string $format
     * @param string $header
     * @param Throwable $e
     * @return string
     */
    public static function exception(string $format, string $header, Throwable $e): string
    {
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
            $e->getMessage(),
            $e->getTraceAsString(),
        ];

        return str_replace($placeholders, $values, $format);
    }

    /**
     * Formatting log messages
     *
     * @param string $format
     * @param int $taskId
     * @param string $taskType
     * @param string $name
     * @param string $message
     * @param string $description
     * @return string
     */
    public static function logMessage(
        string $format,
        int    $taskId,
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

        return str_replace($placeholders, $values, $format);
    }
}