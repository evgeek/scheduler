<?php

namespace Evgeek\Scheduler\Wrapper;

use Carbon\Carbon;
use Evgeek\Scheduler\Config;
use Evgeek\Scheduler\Handler\LockHandlerInterface;
use Evgeek\Scheduler\Constant\PhpErrors;
use Evgeek\Scheduler\Task\TaskInterface;
use Evgeek\Scheduler\Constant\Mode;
use Evgeek\Scheduler\Tools\Cron;
use Evgeek\Scheduler\Tools\Formatter;
use Evgeek\Scheduler\Tools\Time;
use Exception;
use Throwable;

class TaskWrapper
{
    /**
     * The maximum length of the task name. Longer names will be truncated.
     * @var int
     */
    private const MAX_NAME_LENGTH = 128;

    /** @var Config */
    private $config;

    /** @var Cron */
    private $cron;
    /** @var LockHandlerInterface */
    private $handler;
    /** @var TaskInterface */
    private $task;
    /** @var int */
    private $taskId;

    /** @var string */
    private $name;
    /** @var string */
    private $description = '';
    /** @var bool */
    private $preventOverlapping;
    /** @var int */
    private $lockResetTimeout;
    /** @var int */
    private $tries;
    /** @var int */
    private $tryDelay;

    /** @var LoggerWrapper */
    private $log;

    /** @var ?string */
    private $oldErrorHandler;

    /**
     * Task wrapper containing the scheduler parameters and launch logic.
     * @param LockHandlerInterface $handler
     * @param Config $config
     * @param TaskInterface $task
     * @param int $taskId task id from Scheduler's tasks array position
     * @throws Exception
     */
    public function __construct(LockHandlerInterface $handler, Config $config, TaskInterface $task, int $taskId)
    {
        $this->cron = new Cron($config);

        $this->log = $config->getLoggerWrapper();
        $this->handler = $handler;
        $this->task = $task;
        $this->name = $task->getName();
        $this->taskId = $taskId;

        $this->preventOverlapping($config->getDefaultPreventOverlapping());
        $this->lockResetTimeout($config->getDefaultLockResetTimeout());
        $this->tries($config->getDefaultTries());
        $this->tryDelay($config->getDefaultTryDelay());

        $this->config = $config;
    }

    /**
     * Calculating whether it is time to start the task, and launch if it is time
     * @throws Exception
     */
    public function dispatch(): void
    {
        $cronMode = $this->cron->getMode();
        $cronDelay = $this->cron->getMinutes();

        if ($cronMode === Mode::NONE) {
            throw new Exception("Has no repeat mode configured. " .
                "Do this with the 'every', 'delay' and/or any intervals methods");
        }
        $startTime = Carbon::now();
        $this->logDebug("Checking if it's time to start");


        if (!$this->cron->inInterval($startTime)) {
            $this->logDebug("It's not time yet. Wait for an appropriate time interval");
            return;
        }

        $lastLaunch = $this->handler->getLastLaunch($this->taskId, $this->task->getType(), $this->name, $this->description);

        if ($lastLaunch === null) {
            $this->logDebug("Hasn't started before or has been changed");
            $this->launchTask($startTime);
            return;
        }


        if ($cronMode === Mode::SINGLE && $this->cron->sameInterval($startTime, $lastLaunch->startTime)) {
            $delay = $startTime->diffInMinutes($lastLaunch->startTime);
            $this->logDebug("Last launch was started in the current time interval ($delay min ago). Wait for a new time interval");
            return;
        }

        $timeDiff = $startTime->diffInMinutes($lastLaunch->startTime);
        if ($cronMode === Mode::EVERY && $timeDiff < $cronDelay) {
            $this->logDebug("Less than the specified delay has passed since the start of the previous " .
                "launch ($timeDiff/$cronDelay min). Wait " . ($cronDelay - $timeDiff) . " min.");
            return;
        }

        $timeDiff = $lastLaunch->endTime === null ? null : $startTime->diffInMinutes($lastLaunch->endTime);
        if ($cronMode === Mode::DELAY && ($timeDiff !== null && $timeDiff < $cronDelay)) {
            $this->logDebug("Less than the specified delay has passed since the end of the previous " .
                "launch ($timeDiff/$cronDelay min). Wait " . ($cronDelay - $timeDiff) . " min.");
            return;
        }

        if (!$lastLaunch->isWorking) {
            $this->launchTask($startTime);
            return;
        }


        //isWorking === true
        $runtime = Carbon::now()->diffInMinutes($lastLaunch->startTime);
        $this->logDebug("The previous launch is still running (already $runtime min)");

        if ($runtime >= $this->lockResetTimeout) {
            $this->logError("The current runtime is bigger than the reset timeout ($this->lockResetTimeout min). Reset lock");
            $this->handler->resetLock($lastLaunch->id);
            $this->launchTask($startTime);
            return;
        }

        if ($cronMode === Mode::DELAY) {
            $this->logDebug("The current runtime is less then the reset timeout ($this->lockResetTimeout min). " .
                "DELAY mode can't overlap. Waiting for task completion or lock release (" .
                ($this->lockResetTimeout - $runtime) . " min)");
            return;
        }

        if ($this->preventOverlapping) {
            $this->logDebug("The current runtime is less than the reset timeout " .
                "($this->lockResetTimeout min), and overlap is not allowed. Keep working");
            return;
        }
        $this->logDebug("Overlap allowed");

        if ($cronMode === Mode::SINGLE) {
            $this->logDebug("Was started in the previous time interval. Reset lock and start overlapping");
            $this->handler->resetLock($lastLaunch->id);
            $this->launchTask($startTime);
            return;
        }

        //Mode::EVERY
        $timeDiff = $startTime->diffInMinutes($lastLaunch->startTime);
        $this->logDebug("More than the set delay ($cronDelay min) has passed since " .
            "the start of the last launch ($timeDiff min). Reset lock and start overlapping");
        $this->handler->resetLock($lastLaunch->id);
        $this->launchTask($startTime);
    }

    /**
     * Launches and relaunches task, and log the results
     * @param Carbon $startTime
     * @param int|null $launchId
     * @param int $try
     * @throws Exception
     */
    private function launchTask(Carbon $startTime, int $launchId = null, int $try = 1): void
    {
        $this->oldErrorHandler = set_error_handler(
            function ($code, $message, $file, $line) {
                $this->phpErrorHandler($code, $message, $file, $line);
            }
        );

        if ($launchId === null) {
            $this->logDebug("Launched (try $try/$this->tries)");
            $launchId = $this->handler->startNewLaunch($this->taskId);
        } else {
            $this->logDebug("Restarted (try $try/$this->tries)");
            $launchId = $this->handler->restartExistingLaunch($launchId);
        }

        try {
            $this->task->dispatch();
            $this->handler->completeLaunchSuccessfully($launchId);
            $this->logDebug("Completed in " . Time::diffString($startTime));
        } catch (Throwable $e) {
            $header = "Failed (try $try/$this->tries) in " . Time::diffString($startTime);
            $message = Formatter::exception(
                $this->config->getLogExceptionFormat(),
                $this->config->getMaxExceptionMsgLength(),
                $header,
                $e
            );
            $errors = $this->handler->completeLaunchUnsuccessfully($launchId, $message);
            if ($errors < $this->tries) {
                $this->logDebug($message);
                sleep($this->tryDelay);
                $this->launchTask($startTime, $launchId, ++$errors);
            } else {
                $header = "Failed in " . Time::diffString($startTime);
                $this->logError($header, $e);
            }
        }
    }

    /**
     * How often, in minutes, tasks will be launched (counted from start time of previous launch).
     * If intervals are specified, the task will launch only in them.
     * The task can work in only one mode - either every or a delay.
     * @param int $minutes
     * @return TaskWrapper
     * @throws Exception
     */
    public function every(int $minutes): TaskWrapper
    {
        $this->cron->every($minutes);

        return $this;
    }

    /**
     * How many minutes after the previous launch the next one will start (counted from end time of previous launch).
     * If intervals are specified, the task will launch only in them.
     * The task can work in only one mode - either every or a delay.
     * @param int $minutes
     * @return $this
     * @throws Exception
     */
    public function delay(int $minutes): TaskWrapper
    {
        $this->cron->delay($minutes);

        return $this;
    }

    /**
     * Add launch interval. Time must be passed in 'H:i' format (23:59 for example).
     * You can set multiple intervals, but they must not overlap.
     * If every/delay is not specified, the task will be executed once per interval. If given, then according to them.
     * @param string $startTime
     * @param string $endTime
     * @return $this
     * @throws Exception
     */
    public function addInterval(string $startTime, string $endTime): TaskWrapper
    {
        $this->cron->addInterval($startTime, $endTime);

        return $this;
    }

    /**
     * Launch the task only on the specified days of week.
     * Days must be passed by array with int number of day or text representation in any case,
     *  for example: ['Mo', 'TUE', 'wednesday', 4, \Evgeek\Scheduler\Constant\Days::FRIDAY].
     * New call of the method will add new days.
     * @param int|string[] $days
     * @return $this
     * @throws Exception
     */
    public function daysOfWeek(array $days): TaskWrapper
    {
        $this->cron->setDaysOfWeek($days);

        return $this;
    }

    /**
     * Launch the task only on the specified days of month.
     * Days must be passed by array with int number of day between 1 and 31.
     * New call of the method will add new days.
     * @param int[] $days
     * @return TaskWrapper
     * @throws Exception
     */
    public function daysOfMonth(array $days): TaskWrapper
    {
        $this->cron->setDaysOfMonth($days);

        return $this;
    }

    /**
     * Launch the task only on the specified months.
     * Months must be passed by array with int number of months or text representation in any case, for example:
     *  ['January', 'feb', 'MA', 4, \Evgeek\Scheduler\Constant\Month::MAY].
     * New call of the method will add new months.
     * @param array $months
     * @return $this
     * @throws Exception
     */
    public function months(array $months): TaskWrapper
    {
        $this->cron->setMonths($months);

        return $this;
    }

    /**
     * Launch the task only on the specified years.
     * Years must be passed by array with int number of year between 1900 and 2999.
     * New call of the method will add new years.
     * @param array $years
     * @return $this
     * @throws Exception
     */
    public function years(array $years): TaskWrapper
    {
        $this->cron->setYears($years);

        return $this;
    }

    /**
     * If true, a new instance of the task will not be launched on top of an already launched one.
     * Blocking duration is determined by lockResetTimeout.
     * @param bool $prevent
     * @return $this
     */
    public function preventOverlapping(bool $prevent): TaskWrapper
    {
        $this->preventOverlapping = $prevent;
        return $this;
    }

    /**
     * If the task is launched in locking mode (preventOverlapping = true),
     * this parameter determines how long, in minutes, locking will be released.
     * @param int $minutes
     * @return $this
     * @throws Exception
     */
    public function lockResetTimeout(int $minutes): TaskWrapper
    {
        if ($minutes < 0) {
            throw new Exception('The number of minutes in the locking reset timeout must be greater than or equal to zero.');
        }
        $this->lockResetTimeout = $minutes;
        return $this;
    }

    /**
     * The number of attempts to execute the task in case of an error.
     * @param int $count
     * @return $this
     * @throws Exception
     */
    public function tries(int $count): TaskWrapper
    {
        if ($count <= 0) {
            throw new Exception('The number of attempts must be greater than zero.');
        }
        $this->tries = $count;
        return $this;
    }

    /**
     * Delay before trying again if the task fails.
     * @param int $minutes
     * @return $this
     * @throws Exception
     */
    public function tryDelay(int $minutes): TaskWrapper
    {
        if ($minutes < 0) {
            throw new Exception('The delay before new try must be greater than or equal to zero.');
        }
        $this->tryDelay = $minutes;
        return $this;
    }

    /**
     * Edit name of the task. The maximum length is 128 symbols.
     * @param string $name
     * @return TaskWrapper
     * @throws Exception
     */
    public function name(string $name): TaskWrapper
    {
        if ($name === '') {
            throw new Exception('Task name can\'t be empty.');
        }
        $truncateMark = '(...)';
        if (strlen($name) > static::MAX_NAME_LENGTH) {
            $name = substr($name, 0, static::MAX_NAME_LENGTH - strlen($truncateMark)) . $truncateMark;
        }
        $this->name = $name;
        return $this;
    }

    /**
     * Add description to the task.
     * @param string $text
     * @return TaskWrapper
     */
    public function description(string $text): TaskWrapper
    {
        $this->description = $text;
        return $this;
    }

    /**
     * Returns an array of settings
     * @return array
     * @throws Exception
     */
    public function getSettings(): array
    {
        $cronMinutes = $this->cron->getMinutes();
        $cronMode = $this->cron->getMode();
        switch ($cronMode) {
            case Mode::NONE:
                $mode = 'NONE: - mode not set, task cannot be launched';
                break;
            case Mode::SINGLE:
                $mode = 'SINGLE - launch once per interval';
                break;
            case Mode::EVERY:
                $mode = "EVERY - launches every $cronMinutes min";
                break;
            case Mode::DELAY:
                $mode = "DELAY - launches $cronMinutes min after the end of the previous launch";
                break;
            default:
                throw new Exception('Unknown cron mode');
        }

        return [
            'task_id' => $this->taskId,
            'name' => $this->name,
            'description' => $this->description,
            'mode' => $cronMode,
            'mode_description' => $mode,
            'prevent_overlapping' => $this->preventOverlapping,
            'lock_reset_timeout' => $this->lockResetTimeout,
            'tries' => $this->tries,
            'try_delay' => $this->tryDelay,
            'intervals' => [
                'time' => $this->cron->getIntervals(),
                'days_of_week' => $this->prepareSettingsArray($this->cron->getDaysOfWeek()),
                'days_of_month' => $this->prepareSettingsArray($this->cron->getDaysOfMonth()),
                'months' => $this->prepareSettingsArray($this->cron->getMonths()),
                'years' => $this->prepareSettingsArray($this->cron->getYears()),
            ]
        ];
    }

    /**
     * Helper method for getSettings
     *
     * @param array $arr
     * @return array
     */
    private function prepareSettingsArray(array $arr): array
    {
        ksort($arr);

        $result = [];
        foreach ($arr as $value => $true) {
            $result[] = $value;
        }
        return $result;
    }

    /**
     * Log message to debug channel
     * @param string $message
     */
    public function logDebug(string $message): void
    {
        $formattedMessage = Formatter::logMessage(
            $this->config->getLogMessageFormat(),
            $this->config->getMaxLogMsgLength(),
            $this->taskId,
            $this->task->getType(),
            $this->name,
            $message,
            $this->description
        );

        $this->log->debug($formattedMessage);
    }

    /**
     * Log message to error channel
     * @param string $message
     * @param Throwable|null $e
     */
    public function logError(string $message, ?Throwable $e = null): void
    {
        if ($e !== null) {
            $message = Formatter::exception(
                $this->config->getLogExceptionFormat(),
                $this->config->getMaxExceptionMsgLength(),
                $message,
                $e
            );
        }

        $formattedMessage = Formatter::logMessage(
            $this->config->getLogMessageFormat(),
            $this->config->getMaxLogMsgLength(),
            $this->taskId,
            $this->task->getType(),
            $this->name,
            $message,
            $this->description
        );

        $this->log->error($formattedMessage, $e);
    }

    /**
     * @param $code
     * @param $message
     * @param $file
     * @param $line
     * @throws Exception
     */
    private function phpErrorHandler($code, $message, $file, $line): void
    {
        if ($this->oldErrorHandler !== null) {
            call_user_func($this->oldErrorHandler, $code, $message, $file, $line);
        }

        if (array_key_exists($code, PhpErrors::FATAL)) {
            $errName = PhpErrors::FATAL[$code];
            throw new Exception("(ATTENTION: PHP $errName) - $message", $code);
        } elseif ($this->config->getLogUncaughtErrors()) {
            $message = (PhpErrors::SOFT[$code] ?? 0) . " - $message (code $code, file $file, line $line)";
            $this->config->getLogWarningsToError() ?
                $this->logError($message) :
                $this->logDebug($message);

        }
    }
}