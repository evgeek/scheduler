<?php

namespace Evgeek\Scheduler;

use Evgeek\Scheduler\Wrapper\LoggerWrapper;
use Exception;
use Psr\Log\LoggerInterface;

class Config
{
    /**
     * If true, a debug log will be written.
     * The logging method is defined by the $logger parameter of the constructor
     * @var bool
     */
    private $debugLogging;
    /**
     * If true, an error log will be written.
     * The logging method is defined by the $logger parameter of the constructor
     * @var bool
     */
    private $errorLogging;
    /**
     * PSR-3 is a compatible logger. If null - the log will be sent to the stdout/stderr.
     * @var ?LoggerInterface
     */
    private $logger;
    /**
     * Log level for information/debug messages (DEBUG by default).
     * @var mixed
     */
    private $debugLogLevel;
    /**
     * Log level for error messages (ERROR by default).
     * @var mixed
     */
    private $errorLogLevel;
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
     * @var string
     */
    private $logMessageFormat;
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
     * @param bool $debugLogging If true, a debug log will be written.
     * @param bool $errorLogging If true, an error log will be written.
     * @param ?LoggerInterface $logger PSR-3 is a compatible logger. If null - the log will be sent to the stdout/stderr.
     * @param mixed $debugLogLevel Log level for information/debug messages (DEBUG by default).
     * @param mixed $errorLogLevel Log level for error messages (ERROR by default).
     * @param bool $logUncaughtErrors Registers shutdown function for logging PHP runtime fatal errors
     * @param string $logMessageFormat Log message formatting string. Available task_id, task_type, task_name, message
     *                  and task_description variables. Lowercase for regular case, uppercase - for forced uppercase.
     * @param bool $defaultPreventOverlapping Determines if an overlapping task can be run launched
     * @param int $defaultLockResetTimeout Locking reset timeout in minutes (to prevent freezing tasks)
     * @param int $defaultTries The number of attempts to execute the task in case of an error
     * @param int $defaultTryDelay Delay before new try
     * @param int $minimumIntervalLength Minimum interval in minutes for task's addInterval method.
     *                                   ATTENTION: a low value can cause to skipped tasks, change at your own risk.
     * @throws Exception
     */
    public function __construct(
        bool            $debugLogging = false,
        bool            $errorLogging = true,
        LoggerInterface $logger = null,
                        $debugLogLevel = null,
                        $errorLogLevel = null,
        bool            $logUncaughtErrors = false,
        string          $logMessageFormat = "[{{task_id}}. {{TASK_TYPE}} '{{task_name}}']: {{message}}",
        bool            $commandOutput = false,
        bool            $defaultPreventOverlapping = false,
        int             $defaultLockResetTimeout = 360,
        int             $defaultTries = 1,
        int             $defaultTryDelay = 0,
        int             $minimumIntervalLength = 30
    )
    {
        $this->debugLogging = $debugLogging;
        $this->errorLogging = $errorLogging;
        $this->logger = $logger;
        $this->debugLogLevel = $debugLogLevel;
        $this->errorLogLevel = $errorLogLevel;
        $this->logUncaughtErrors = $logUncaughtErrors;
        $this->logMessageFormat = $logMessageFormat;
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
     * If true, a debug log will be written.
     * The logging method is defined by the $logger parameter of the constructor
     * @return bool
     */
    public function getDebugLogging(): bool
    {
        return $this->debugLogging;
    }

    /**
     * If true, an error log will be written.
     * The logging method is defined by the $logger parameter of the constructor
     * @return bool
     */
    public function getErrorLogging(): bool
    {
        return $this->errorLogging;
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
     *  Log level for information/debug messages (DEBUG by default).
     * @return mixed
     */
    public function getDebugLogLevel()
    {
        return $this->debugLogLevel;
    }

    /**
     * Log level for error messages (ERROR by default).
     * @return mixed
     */
    public function getErrorLogLevel()
    {
        return $this->errorLogLevel;
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
     * @return string
     */
    public function getLogMessageFormat(): string
    {
        return $this->logMessageFormat;
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
