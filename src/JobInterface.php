<?php

namespace Evgeek\Scheduler;

interface JobInterface
{
    /**
     * Starts the execution of a job
     * @return mixed
     */
    public function dispatch(): void;
}