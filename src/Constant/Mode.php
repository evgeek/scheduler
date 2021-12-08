<?php

namespace Evgeek\Scheduler\Constant;

final class Mode
{
    /**
     * The launching mode is not configured.
     * @var int
     */
    public const NONE = 0;

    /**
     * Only intervals are configured. Task will be launched once per interval.
     * @var int
     */
    public const SINGLE = 1;

    /**
     * For periodical launches. The task will be launched every X minutes (calculated from start time of previous launch).
     * If intervals configured - the task must be started only in these intervals.
     * @var int
     */
    public const EVERY = 2;

    /**
     * For periodical launches. The task will be launched within X minutes after the end of previous launch.
     * If intervals configured - the task must be started only in these intervals.
     * @var int
     */
    public const DELAY = 3;
}