<?php

namespace Evgeek\Scheduler\Task;

use Closure;
use Evgeek\Scheduler\Scheduler;
use Exception;
use ReflectionException;
use ReflectionFunction;

class TaskFromClosure extends AbstractTask
{
    /** @var string Task type */
    protected const TYPE = 'closure';
    /** @var Closure */
    protected $closure;

    /**
     * Create scheduler task class from custom closure
     * @param Scheduler $scheduler
     * @param Closure $closure
     * @throws ReflectionException
     * @throws Exception
     */
    public function __construct(Scheduler $scheduler, Closure $closure)
    {
        parent::__construct($scheduler, $this->closure_dump($closure));

        $this->closure = $closure;
    }

    public function dispatch(): void
    {
        $this->closure->call($this);
    }

    /**
     * @param Closure $c
     * @return string
     * @throws ReflectionException
     */
    private function closure_dump(Closure $c): string
    {
        $str = 'function(';
        $r = new ReflectionFunction($c);
        $params = [];
        foreach ($r->getParameters() as $p) {
            $s = '';
            if ($p->isArray()) {
                $s .= 'array ';
            } else if ($p->getClass()) {
                $s .= $p->getClass()->name . ' ';
            }
            if ($p->isPassedByReference()) {
                $s .= '&';
            }
            $s .= '$' . $p->name;
            if ($p->isOptional()) {
                $s .= ' = ' . var_export($p->getDefaultValue(), TRUE);
            }
            $params [] = $s;
        }
        $str .= implode(', ', $params);
        $str .= '){' . PHP_EOL;
        $lines = file($r->getFileName());
        for ($l = $r->getStartLine(); $l < $r->getEndLine(); $l++) {
            $str .= trim($lines[$l]);
        }

        return str_replace(["\r", "\n", "\t"], '', $str);
    }
}