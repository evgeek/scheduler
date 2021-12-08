<?php

namespace Evgeek\Scheduler\Task;

use Evgeek\Scheduler\Wrapper\TaskWrapper;

interface TaskInterface
{
    /**
     * Starts the execution of a task
     * @return void
     */
    public function dispatch(): void;

    /**
     * Returns task name
     * @return string
     */
    public function getName(): string;

    /**
     * Returns task name
     * @return string
     */
    public function getType(): string;

    /**
     * Add task to schedule
     * @return TaskWrapper
     */
    public function schedule(): TaskWrapper;
}