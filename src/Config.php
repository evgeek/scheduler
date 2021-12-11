<?php

namespace Evgeek\Scheduler;

use Evgeek\Scheduler\Wrapper\LoggerWrapper;
use Exception;
use Psr\Log\LoggerInterface;

class Config
{
    /**
     * PSR-3 is a compatible logger. If null - the log will be sent to the STDOUT/STDERR.
     * @var ?LoggerInterface
     */
    private $logger;
    /**
     * False/null - debug log disabled. True - enabled (STDOUT/DEBUG). Or set custom PSR-3 level.
     * @var mixed
     */
    private $debugLog;
    /**
     * False/null - error log disabled. True - enabled (STDERR/ERROR). Or set custom PSR-3 level.
     * @var mixed
     */
    private $errorLog;
    /**
     * Registers shutdown function for logging PHP runtime fatal errors that the scheduler cannot catch
     * @var bool
     */
    private $logUncaughtErrors;
    /**
     * Log message formatting string.
     * Available variables: {{task_id}}, {{task_type}}, {{TASK_TYPE}}, {{task_name}}, {{TASK_NAME}},
     * {{message}}, {{MESSAGE}}, {{task_description}}, {{TASK_DESCRIPTION}}.
     * Lowercase for regular case, uppercase - for forced uppercase.
     * Default format: "[{{task_id}}. {{TASK_TYPE}} '{{task_name}}']: {{message}}"
     * @var string
     */
    private $logMessageFormat;
    /**
     * Mapping specific exceptions to PSR-3 log channels (class => level).
     * @var array
     */
    private $exceptionLogMatching;
    /**
     * Exception formatting string. Available variables: {{header}}, {{code}}, {{class}}, {{message}} and {{stacktrace}}.
     * Default format: "{{header}}\n[code]: {{code}}\n[exception]: {{class}}\n[message]: {{message}}\n[stacktrace]:\n{{stacktrace}}"
     * @var string
     */
    private $logExceptionFormat;
    /**
     * If true, output from bash command tasks will be sent to stdout. Otherwise, it will be suppressed.
     * @var bool
     */
    private $commandOutput;
    /**
     * If true, a new instance of the task will not be launched on top of an already running one.
     * Blocking duration is determined by lockResetTimeout.
     * @var bool
     */
    private $defaultPreventOverlapping;
    /**
     * This parameter determines how long, in minutes, locking will be released.
     * Used to prevent freezing tasks.
     * @var int
     */
    private $defaultLockResetTimeout;
    /**
     * The number of attempts to execute the task in case of an error.
     * @var int
     */
    private $defaultTries;
    /**
     * Delay before trying again if the task fails.
     * @var int
     */
    private $defaultTryDelay;
    /**
     * Minimum interval in minutes for task's addInterval method. It is made due to the fact that the scheduler
     * does not guarantee the start of the task at the exact time, and too small interval can lead to a missed task launch.
     * ATTENTION: use reducing limitation of the interval consciously, at your own risk.
     * @var int
     */
    private $minInterval;

    /**
     * Wrapper around user's PSR-3 logger
     * @var LoggerWrapper
     */
    private $loggerWrapper;

    /**
     * Configure default scheduler parameters
     * @param ?LoggerInterface $logger PSR-3 is a compatible logger. If null - the log will be sent to the STDOUT/STDERR.
     * @param mixed $debugLog False/null - debug log disabled. True - enabled (STDOUT/DEBUG). Or set custom PSR-3 level.
     * @param mixed $errorLog False/null - error log disabled. True - enabled (STDERR/ERROR). Or set custom PSR-3 level.
     * @param bool $logUncaughtErrors Registers shutdown function for logging PHP runtime fatal errors
     * @param ?string $logMessageFormat Log message formatting string. Available {{task_id}}, {{task_type}}, {{task_name}},
     *      {{message}} and {{task_description}} variables. Lowercase for regular case, uppercase - for forced uppercase.
     *      Pass null for default formatting: "[{{task_id}}. {{TASK_TYPE}} '{{task_name}}']: {{message}}"
     * @param ?array $exceptionLogMatching Mapping specific exceptions to PSR-3 log channels (class => level).
     * @param ?string $logExceptionFormat Exception formatting string. Available {{header}}, {{code}}, {{class}},
     *      {{message}} and {{stacktrace}} variables. Pass null for default formatting:
     *      "{{header}}\n[code]: {{code}}\n[exception]: {{class}}\n[message]: {{message}}\n[stacktrace]:\n{{stacktrace}}"
     * @param bool $defaultPreventOverlapping Determines if an overlapping task can be run launched
     * @param int $defaultLockResetTimeout Locking reset timeout in minutes (to prevent freezing tasks)
     * @param int $defaultTries The number of attempts to execute the task in case of an error
     * @param int $defaultTryDelay Delay before new try
     * @param int $minimumIntervalLength Minimum interval in minutes for task's addInterval method.
     *                                   ATTENTION: a low value can cause to skipped tasks, change at your own risk.
     * @throws Exception
     */
    public function __construct(
        LoggerInterface $logger = null,
                        $debugLog = false,
                        $errorLog = true,
        bool            $logUncaughtErrors = false,
        ?string         $logMessageFormat = null,
        ?array          $exceptionLogMatching = [],
        ?string         $logExceptionFormat = null,
        bool            $commandOutput = false,
        bool            $defaultPreventOverlapping = false,
        int             $defaultLockResetTimeout = 360,
        int             $defaultTries = 1,
        int             $defaultTryDelay = 0,
        int             $minimumIntervalLength = 30
    )
    {
        $this->logger = $logger;
        $this->debugLog = $debugLog;
        $this->errorLog = $errorLog;
        $this->logUncaughtErrors = $logUncaughtErrors;
        $this->logMessageFormat = $logMessageFormat ?? "[{{task_id}}. {{TASK_TYPE}} '{{task_name}}']: {{message}}";
        $this->exceptionLogMatching = $exceptionLogMatching ?? [];
        $this->logExceptionFormat = $logExceptionFormat ?? '{{header}}' . PHP_EOL . '[code]: {{code}}' . PHP_EOL .
            '[exception]: {{class}}' . PHP_EOL . '[message]: {{message}}' . PHP_EOL . '[stacktrace]:' . PHP_EOL . '{{stacktrace}}';
        $this->commandOutput = $commandOutput;
        $this->defaultPreventOverlapping = $defaultPreventOverlapping;

        if ($defaultLockResetTimeout < 0) {
            throw new Exception('The number of minutes in the locking reset timeout must be greater than or equal to zero.');
        }
        $this->defaultLockResetTimeout = $defaultLockResetTimeout;

        if ($defaultTries <= 0) {
            throw new Exception('The number of attempts must be greater than zero.');
        }
        $this->defaultTries = $defaultTries;

        if ($defaultTryDelay < 0) {
            throw new Exception('The delay before new try must be greater than or equal to zero.');
        }
        $this->defaultTryDelay = $defaultTryDelay;

        if ($minimumIntervalLength <= 0) {
            throw new Exception('The minimum interval must be greater than zero.');
        }
        $this->minInterval = $minimumIntervalLength;

        $this->loggerWrapper = new LoggerWrapper($this);
    }

    /**
     * PSR-3 is a compatible logger. If null - the log will be sent to the stdout/stderr.
     * @return LoggerInterface|null
     */
    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    /**
     * False/null - debug log disabled. True - enabled (DEBUG for PSR-3 logger, or STDOUT, if logger not set).
     * Or you can set custom PSR-3  log level for debug messages.
     * @return mixed
     */
    public function getDebugLog()
    {
        return $this->debugLog;
    }

    /**
     * False/null - error log disabled. True - enabled (ERROR for PSR-3 logger, or STDERR, if logger not set).
     * Or you can set custom PSR-3  log level for error messages.
     * @return mixed
     */
    public function getErrorLog()
    {
        return $this->errorLog;
    }

    /**
     * Registers shutdown function for logging PHP runtime fatal errors that the scheduler cannot catch
     * @return bool
     */
    public function getLogUncaughtErrors(): bool
    {
        return $this->logUncaughtErrors;
    }

    /**
     * Log message formatting string.
     * Available variables: {{task_id}}, {{task_type}}, {{TASK_TYPE}}, {{task_name}}, {{TASK_NAME}},
     * {{message}}, {{MESSAGE}}, {{task_description}}, {{TASK_DESCRIPTION}}.
     * Lowercase for regular case, uppercase - for forced uppercase.
     * Default format: "[{{task_id}}. {{TASK_TYPE}} '{{task_name}}']: {{message}}"
     * @return string
     */
    public function getLogMessageFormat(): string
    {
        return $this->logMessageFormat;
    }

    /**
     * Mapping specific exceptions to PSR-3 log channels (class => level).
     * @return array
     */
    public function getExceptionLogMatching(): array
    {
        return $this->exceptionLogMatching;
    }

    /**
     * Exception formatting string. Available variables: {{header}}, {{code}}, {{class}}, {{message}} and {{stacktrace}}.
     * Default format: "{{header}}\n[code]: {{code}}\n[exception]: {{class}}\n[message]: {{message}}\n[stacktrace]:\n{{stacktrace}}"
     * @return string
     */
    public function getLogExceptionFormat(): string
    {
        return $this->logExceptionFormat;
    }

    /**
     * If true, output from bash command tasks will be sent to stdout. Otherwise, it will be suppressed.
     * @return bool
     */
    public function getCommandOutput(): bool
    {
        return $this->commandOutput;
    }

    /**
     * If true, a new instance of the task will not be launched on top of an already running one.
     * Blocking duration is determined by lockResetTimeout.
     * @return bool
     */
    public function getDefaultPreventOverlapping(): bool
    {
        return $this->defaultPreventOverlapping;
    }

    /**
     * This parameter determines how long, in minutes, locking will be released.
     * Used to prevent freezing tasks.
     * @return int
     */
    public function getDefaultLockResetTimeout(): int
    {
        return $this->defaultLockResetTimeout;
    }

    /**
     * The number of attempts to execute the task in case of an error.
     * @return int
     */
    public function getDefaultTries(): int
    {
        return $this->defaultTries;
    }

    /**
     * Delay before trying again if the task fails.
     * @return int
     */
    public function getDefaultTryDelay(): int
    {
        return $this->defaultTryDelay;
    }

    /**
     * Minimum interval in minutes for task's addInterval method. It is made due to the fact that the scheduler
     * does not guarantee the start of the task at the exact time, and too small interval can lead to a missed task launch.
     * ATTENTION: use reducing limitation of the interval consciously, at your own risk.
     * @return int
     */
    public function getMinimumIntervalLength(): int
    {
        return $this->minInterval;
    }

    /**
     * Wrapper around user's PSR-3 logger
     * @return LoggerWrapper
     */
    public function getLoggerWrapper(): LoggerWrapper
    {
        return $this->loggerWrapper;
    }
}
