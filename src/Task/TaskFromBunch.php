<?php

namespace Evgeek\Scheduler\Task;

use Evgeek\Scheduler\Scheduler;
use Exception;

class TaskFromBunch extends AbstractTask
{
    /** @var string Task type */
    protected const TYPE = 'bunch';
    /** @var TaskInterface[] */
    protected $bunch;

    /**
     * Create scheduler task class from array of tasks
     * @param Scheduler $scheduler
     * @param TaskInterface[] $bunch
     * @throws Exception
     */
    public function __construct(Scheduler $scheduler, array $bunch)
    {
        $name = '';
        foreach ($bunch as $key => $task) {
            if (!$task instanceof TaskInterface) {
                throw new Exception("Task [$key] in a bunch must implement TaskInterface. " .
                    "Use Scheduler->task method to create task for a bunch.");
            }
            $taskName = $key . '::' . $task->getType() . '::' . $task->getName();
            $name .= $name === '' ? $taskName : "; $taskName";
        }

        parent::__construct($scheduler, $name);

        $this->bunch = $bunch;
    }

    public function dispatch(): void
    {
        foreach ($this->bunch as $bunch) {
            $bunch->dispatch();
        }
    }
}