<?php

namespace Evgeek\Scheduler\Task;

use Evgeek\Scheduler\Config;
use Evgeek\Scheduler\Scheduler;
use Exception;

class TaskFromCommand extends AbstractTask
{
    /** @var string Task type */
    protected const TYPE = 'command';
    /** @var string */
    private $command;
    /** @var Config */
    private $config;

    /**
     * Create scheduler task class from bash command
     * @param Scheduler $scheduler
     * @param string $command
     * @param Config $config
     * @throws Exception
     */
    public function __construct(Scheduler $scheduler, string $command, Config $config)
    {
        parent::__construct($scheduler, $command);

        $this->command = $command;
        $this->config = $config;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function dispatch(): void
    {
        $redirectedCommand = "($this->command) 2>&1";


        if ($this->config->getCommandOutput()) {
            $lastLine = system($redirectedCommand, $code);
        } else {
            exec($redirectedCommand, $output, $code);
            $lastLine = end($output);
        }

        if ($code !== 0) {
            throw new Exception($lastLine, $code);
        }
    }
}