<?php

namespace Evgeek\Scheduler\Task;

use Evgeek\Scheduler\Scheduler;
use Exception;

class TaskFromFile extends AbstractTask
{
    /** @var string Task type */
    protected const TYPE = 'file';
    /** @var string */
    private $path;

    /**
     * Create scheduler task class from path to php file
     * @param Scheduler $scheduler
     * @param string $path
     * @throws Exception
     */
    public function __construct(Scheduler $scheduler, string $path)
    {
        if (static::validatePath($path) === null) {
            throw new Exception("$path is not path to PHP file");
        }
        parent::__construct($scheduler, $path);

        $this->path = $path;
    }

    /**
     * @inheritDoc
     */
    public function dispatch(): void
    {
        require $this->path;
    }

    /**
     * Checks if a string is a path to a php file. Returns null if not, otherwise - returns realpath.
     * @param string $path
     * @return ?string
     */
    public static function validatePath(string $path): ?string
    {
        $validatedPath = realpath($path) === false ? realpath(__DIR__ . "/$path") : realpath($path);
        if ($validatedPath && substr($validatedPath, -4) === '.php') {
            return $validatedPath;
        }

        return null;
    }
}