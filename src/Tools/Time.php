<?php

namespace Evgeek\Scheduler\Tools;

use Carbon\Carbon;

class Time
{
    /**
     * Create time difference string for pretty logging
     * @param Carbon $startTime
     * @return string
     */
    public static function diffString(Carbon $startTime): string
    {
        $hours = $startTime->diffInHours(Carbon::now());
        $hours = $hours === 0 ? '' : "{$hours}h";
        $duration = $startTime->diff(Carbon::now())->format('%Im %Ss');

        return (str_starts_with($duration, '00') && $hours === '') ? substr($duration, 4) : "$hours $duration";
    }
}