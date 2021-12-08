<?php

namespace Evgeek\Scheduler;

use Evgeek\Scheduler\Wrapper\LoggerWrapper;
use Exception;
use Psr\Log\LoggerInterface;

class Config
{
    /** @var bool */
    private $debugLogging;
    /** @var bool */
    private $errorLogging;
    /** @var ?LoggerInterface */
    private $logger;
    /** @var mixed */
    private $debugLogLevel;
    /** @var mixed */
    private $errorLogLevel;
    /** @var bool */
    private $logUncaughtErrors;
    /** @var string */
    private $logMessageFormat;
    /** @var bool */
    private $commandOutput;
    /** @var bool */
    private $defaultPreventOverlapping;
    /** @var int */
    private $defaultLockResetTimeout;
    /** @var int */
    private $defaultTries;
    /** @var int */
    private $defaultTryDelay;
    /** @var int */
    private $minInterval;


    /** @var LoggerWrapper */
    private $loggerWrapper;

    /**
     * Configure default scheduler parameters
     * @param bool $debugLogging If true, a debug log will be written.
     * @param bool $errorLogging If true, an error log will be written.
     * @param ?LoggerInterface $logger PSR-3 is a compatible logger. If null - the log will be sent to the stdout/stderr.
     * @param mixed $debugLogLevel Log level for information/debug messages (DEBUG by default).
     * @param mixed $errorLogLevel Log level for error messages (ERROR by default).
     * @param bool $logUncaughtErrors Registers shutdown function for logging PHP runtime fatal errors
     * @param string $logMessageFormat Log message formatting string.
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
        $this
            ->setDebugLogging($debugLogging)
            ->setErrorLogging($errorLogging)
            ->setLogger($logger)
            ->setDebugLogLevel($debugLogLevel)
            ->setErrorLogLevel($errorLogLevel)
            ->setLogUncaughtErrors($logUncaughtErrors)
            ->setLogMessageFormat($logMessageFormat)
            ->setCommandOutput($commandOutput)
            ->setDefaultPreventOverlapping($defaultPreventOverlapping)
            ->setDefaultLockResetTimeout($defaultLockResetTimeout)
            ->setDefaultTries($defaultTries)
            ->setDefaultTryDelay($defaultTryDelay)
            ->setMinimumIntervalLength($minimumIntervalLength);
    }

    /**
     * If true, a debug log will be written.
     * The logging method is defined by the setLogger function
     * @param bool $enable
     * @return $this
     */
    public function setDebugLogging(bool $enable = false): Config
    {
        $this->debugLogging = $enable;
        return $this;
    }

    /**
     * If true, a debug log will be written.
     * The logging method is defined by the setLogger function
     * @return bool
     */
    public function getDebugLogging(): bool
    {
        return $this->debugLogging;
    }

    /**
     * If true, an error log will be written.
     * The logging method is defined by the setLogger function
     * @param bool $enable
     * @return $this
     */
    public function setErrorLogging(bool $enable = true): Config
    {
        $this->errorLogging = $enable;
        return $this;
    }

    /**
     * If true, an error log will be written.
     * The logging method is defined by the setLogger function
     * @return bool
     */
    public function getErrorLogging(): bool
    {
        return $this->errorLogging;
    }

    /**
     * PSR-3 is a compatible logger. If null - the log will be sent to the stdout/stderr.
     * @param LoggerInterface|null $logger
     * @return $this
     */
    public function setLogger(LoggerInterface $logger = null): Config
    {
        $this->logger = $logger;
        return $this;
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
     * Log level for information/debug messages (DEBUG by default)
     * @param null $level
     * @return $this
     */
    public function setDebugLogLevel($level = null): Config
    {
        $this->debugLogLevel = $level;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDebugLogLevel()
    {
        return $this->debugLogLevel;
    }

    /**
     * Log level for error messages (ERROR by default)
     * @param null $level
     * @return $this
     */
    public function setErrorLogLevel($level = null): Config
    {
        $this->errorLogLevel = $level;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getErrorLogLevel()
    {
        return $this->errorLogLevel;
    }

    /**
     * Registers shutdown function for logging PHP runtime fatal errors that the scheduler cannot catch
     * @param bool $enable
     * @return $this
     */
    public function setLogUncaughtErrors(bool $enable = false): Config
    {
        $this->logUncaughtErrors = $enable;
        return $this;
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
     * Lowercase for regular case, uppercase - for forced uppercase
     * @param string $format
     * @return $this
     */
    public function setLogMessageFormat(string $format = "[{{task_id}}. {{TASK_TYPE}} '{{task_name}}']: {{message}}"): Config
    {
        $this->logMessageFormat = $format;
        return $this;
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
     * @param bool $enable
     * @return $this
     */
    public function setCommandOutput(bool $enable = false): Config
    {
        $this->commandOutput = $enable;
        return $this;
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
     * @param bool $prevent
     * @return $this
     */
    public function setDefaultPreventOverlapping(bool $prevent = false): Config
    {
        $this->defaultPreventOverlapping = $prevent;
        return $this;
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
     * @param int $minutes
     * @return $this
     * @throws Exception
     */
    public function setDefaultLockResetTimeout(int $minutes = 360): Config
    {
        if ($minutes < 0) {
            throw new Exception('The number of minutes in the locking reset timeout must be greater than or equal to zero.');
        }
        $this->defaultLockResetTimeout = $minutes;
        return $this;
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
     * @param int $count
     * @return $this
     * @throws Exception
     */
    public function setDefaultTries(int $count = 1): Config
    {
        if ($count <= 0) {
            throw new Exception('The number of attempts must be greater than zero.');
        }
        $this->defaultTries = $count;
        return $this;
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
     * @param int $minutes
     * @return $this
     * @throws Exception
     */
    public function setDefaultTryDelay(int $minutes = 0): Config
    {
        if ($minutes < 0) {
            throw new Exception('The delay before new try must be greater than or equal to zero.');
        }
        $this->defaultTryDelay = $minutes;
        return $this;
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
     * @param int $minutes
     * @return $this
     * @throws Exception
     */
    public function setMinimumIntervalLength(int $minutes = 30): Config
    {
        if ($minutes <= 0) {
            throw new Exception('The minimum interval must be greater than zero.');
        }
        $this->minInterval = $minutes;
        return $this;
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
     * Fabric for logger wrapper around user's PSR-3 logger
     * @return LoggerWrapper
     */
    public function getLoggerWrapper(): LoggerWrapper
    {
        $this->loggerWrapper = $this->loggerWrapper ?? new LoggerWrapper($this);
        return $this->loggerWrapper;
    }
}
