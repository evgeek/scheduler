<?php

namespace Evgeek\Scheduler\Task;

use Evgeek\Scheduler\Scheduler;
use Evgeek\Scheduler\Wrapper\TaskWrapper;
use Exception;

abstract class AbstractTask implements TaskInterface
{
    /** @var string Task type */
    protected const TYPE = '';
    /** @var Scheduler */
    private $scheduler;
    /** @var string */
    private $name;

    /**
     * Creates a common scheduler task class from various sources (jobs, batches, etc.)
     * @param Scheduler $scheduler
     * @param string $name
     * @throws Exception
     */
    public function __construct(Scheduler $scheduler, string $name)
    {
        if (static::TYPE === '') {
            throw new Exception("Task type for " . static::class . ' not specified.');
        }
        $this->scheduler = $scheduler;
        $this->name = $name;
    }

    /**
     * @inheritDoc
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     * @return TaskWrapper
     * @throws Exception
     */
    public function schedule(): TaskWrapper
    {
        return $this->scheduler->schedule($this);
    }

    /**
     * @inheritDoc
     * @return string
     */
    public function getType(): string
    {
        return static::TYPE;
    }
}