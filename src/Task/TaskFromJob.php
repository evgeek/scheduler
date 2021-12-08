<?php

namespace Evgeek\Scheduler\Task;

use Evgeek\Scheduler\JobInterface;
use Evgeek\Scheduler\Scheduler;
use Exception;

class TaskFromJob extends AbstractTask
{
    /** @var string Task type */
    protected const TYPE = 'job';
    /** @var JobInterface */
    private $job;

    /**
     * Create scheduler task class from custom job
     * @param Scheduler $scheduler
     * @param JobInterface $job
     * @throws Exception
     */
    public function __construct(Scheduler $scheduler, JobInterface $job)
    {
        parent::__construct($scheduler, get_class($job));

        $this->job = $job;
    }

    /**
     * @inheritDoc
     */
    public function dispatch(): void
    {
        $this->job->dispatch();
    }
}