<?php

namespace Evgeek\Scheduler;

use Carbon\Carbon;
use Evgeek\Scheduler\Handler\LockHandlerInterface;
use Evgeek\Scheduler\Constant\PhpErrors;
use Evgeek\Scheduler\Task\TaskFromJob;
use Evgeek\Scheduler\Task\TaskFromBunch;
use Evgeek\Scheduler\Task\TaskFromClosure;
use Evgeek\Scheduler\Task\TaskFromFile;
use Evgeek\Scheduler\Task\TaskFromCommand;
use Evgeek\Scheduler\Task\TaskInterface;
use Evgeek\Scheduler\Tools\Time;
use Evgeek\Scheduler\Wrapper\LoggerWrapper;
use Evgeek\Scheduler\Wrapper\TaskWrapper;
use Exception;
use Throwable;

class Scheduler
{
    /** @var LockHandlerInterface */
    private $handler;
    /** @var LoggerWrapper */
    private $log;
    /** @var Config */
    private $config;
    /** @var TaskWrapper[] */
    private $tasks = [];
    /** @var bool */
    private $run = false;

    /**
     * @param LockHandlerInterface $handler
     * @param Config|null $config
     */
    public function __construct(LockHandlerInterface $handler, Config $config = null)
    {
        $this->handler = $handler;
        $this->config = $config ?? new Config();
        $this->log = $this->config->getLoggerWrapper();

        if ($this->config->getLogUncaughtErrors()) {
            register_shutdown_function(function () {
                $this->fatal_handler();
            });
        }
    }


    /**
     * Run Scheduler
     */
    public function run(): void
    {
        $this->run = true;
        $startTime = Carbon::now();
        $this->log->debug('[Scheduler]: Launching the scheduler');
        foreach ($this->tasks as $task) {
            try {
                $task->dispatch();
            } catch (Throwable $e) {
                $task->logError("Can't be started", $e);
            }
        }
        $this->log->debug('[Scheduler]: Completed in ' . Time::diffString($startTime));
        $this->run = false;
    }

    /**
     * Add task to schedule
     * @throws Exception
     */
    public function schedule(TaskInterface $task): TaskWrapper
    {
        return $this->wrapTask($task);
    }

    /**
     * Returns an array with all tasks settings
     * @return array
     * @throws Exception
     */
    public function getTasksSettings(): array
    {
        $settings = [];
        foreach ($this->tasks as $key => $task) {
            $settings[$key] = $task->getSettings();
        }

        return $settings;
    }

    /**
     * Takes a custom class that implements JobInterface and converts it to Task
     * @param $task
     * @return TaskInterface
     * @throws Exception
     */
    public function task($task): TaskInterface
    {
        if ($task instanceof JobInterface) {
            return new TaskFromJob($this, $task);
        }

        if (is_array($task)) {
            return new TaskFromBunch($this, $task);
        }

        if (is_callable($task)) {
            return new TaskFromClosure($this, $task);
        }

        if (is_string($task)) {
            $path = TaskFromFile::validatePath($task);
            if ($path !== null) {
                return new TaskFromFile($this, $path);
            }

            return new TaskFromCommand($this, $task, $this->config);
        }

        throw new Exception('Unknown task type');
    }


    /**
     * Wrap different types of tasks to class with Scheduler parameters
     * @param TaskInterface $task
     * @return TaskWrapper
     * @throws Exception
     */
    private function wrapTask(TaskInterface $task): TaskWrapper
    {
        return $this->tasks[] = new TaskWrapper($this->handler, $this->config, $task, count($this->tasks));
    }

    /**
     * Logging PHP fatal errors
     */
    private function fatal_handler(): void
    {
        $error = error_get_last();

        if ($error !== NULL) {
            $message = $error["message"] ?? "shutdown";
            $code = $error["type"] ?? E_CORE_ERROR;
            $file = $error["file"] ?? "unknown file";
            $line = $error["line"] ?? 0;

            if (array_key_exists($code, PhpErrors::FATAL)) {
                $message = "[message]: $message" . PHP_EOL . "[file]: $file on line $line";
                if ($this->run === true) {
                    $message = "[Scheduler]: ATTENTION: Scheduler crashed due to PHP " . PhpErrors::FATAL[$code] .
                        " error (code $code). " . PHP_EOL . "Check the lock status of the task - in this situation, " .
                        "the Scheduler cannot release it automatically before Lock Reset Timeout has expired." .
                        PHP_EOL . $message;
                } else {
                    $message = "[Scheduler]: Uncaught error" . PHP_EOL . $message;
                }
                $this->log->error($message);
            }
        }
    }
}