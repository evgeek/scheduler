<?php

namespace Evgeek\Scheduler\Tools;

use Carbon\Carbon;
use Evgeek\Scheduler\Config;
use Evgeek\Scheduler\Constant\Days;
use Evgeek\Scheduler\Constant\Mode;
use Evgeek\Scheduler\Constant\Month;
use Exception;
use Throwable;

class Cron
{
    /** @var Config */
    private $config;

    /** @var int */
    private $mode = Mode::NONE;
    /** @var int */
    private $minutes = 0;
    /** @var array */
    private $intervals = [];
    /** @var array */
    private $daysOfWeek = [];
    /** @var array */
    private $daysOfMonth = [];
    /** @var array */
    private $months = [];
    /** @var array */
    private $years = [];

    /**
     * Container and handler for all parameters of the task's time intervals.
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Returns cron mode. The modes are described in the \Evgeek\Scheduler\Constant\Mode
     * @return int
     * @throws Exception
     */
    public function getMode(): int
    {
        if (
            $this->mode !== Mode::NONE &&
            $this->mode !== Mode::SINGLE &&
            $this->mode !== Mode::EVERY &&
            $this->mode !== Mode::DELAY
        ) {
            throw new Exception('Unknown cron mode');
        }
        return $this->mode;
    }

    /**
     * @return int
     */
    public function getMinutes(): int
    {
        return $this->minutes;
    }

    /**
     * @param Carbon $time
     * @return bool
     * @throws Exception
     */
    public function inInterval(Carbon $time): bool
    {
        switch ($this->mode) {
            case Mode::NONE:
                return false;

            case  Mode::SINGLE:
                return $this->getIntervalIndex($time) !== null;

            default:
                return !$this->issetInterval() || $this->getIntervalIndex($time) !== null;
        }
    }

    /**
     * @param Carbon $time1
     * @param Carbon $time2
     * @return bool
     * @throws Exception
     */
    public function sameInterval(Carbon $time1, Carbon $time2): bool
    {
        return $this->getIntervalIndex($time1) === $this->getIntervalIndex($time2);
    }

    /**
     * @param Carbon $time
     * @return string|null
     * @throws Exception
     */
    private function getIntervalIndex(Carbon $time): ?string
    {
        if ($this->mode === Mode::SINGLE && !$this->issetInterval()) {
            return null;
        }

        $year = false;
        $year = array_key_exists($time->year, $this->years) ? $time->year : $year;
        $year = count($this->years) === 0 ? 0 : $year;
        if ($year === false) {
            return null;
        }

        $month = false;
        $month = array_key_exists($time->month, $this->months) ? $time->month : $month;
        $month = count($this->months) === 0 ? 0 : $month;
        if ($month === false) {
            return null;
        }

        $dayOfWeek = false;
        $dayOfWeek = array_key_exists($time->dayOfWeekIso, $this->daysOfWeek) ? $time->dayOfWeekIso : $dayOfWeek;
        $dayOfWeek = count($this->daysOfWeek) === 0 ? 0 : $dayOfWeek;

        $dayOfMonth = false;
        $dayOfMonth = array_key_exists($time->day, $this->daysOfMonth) ? $time->day : $dayOfMonth;
        $dayOfMonth = count($this->daysOfMonth) === 0 ? 0 : $dayOfMonth;

        if (
            ($dayOfWeek === false && $dayOfMonth === false) ||
            ($dayOfWeek === 0 && $dayOfMonth === false) ||
            ($dayOfWeek === false && $dayOfMonth === 0)
        ) {
            return null;
        }
        $dayOfWeek = $dayOfWeek === false ? -1 : $dayOfWeek;
        $dayOfMonth = $dayOfMonth === false ? -1 : $dayOfMonth;

        $intervals = false;
        $intervals = count($this->intervals) === 0 ? 0 : $intervals;

        foreach ($this->intervals as $interval) {
            $start = $interval['start'] ?? null;
            $end = $interval['end'] ?? null;
            if ($start === null || $end === null) {
                throw new Exception('Invalid interval definition');
            }
            $this->validateTimeString($start);
            $this->validateTimeString($end);

            $startTime = Carbon::createFromFormat('H:i', $start);
            $endTime = Carbon::createFromFormat('H:i', $end);

            if ($time >= $startTime && $time <= $endTime) {
                $intervals = "$start-$end";
                break;
            }
        }
        if ($intervals === false) {
            return null;
        }

        return "$year::$month::$dayOfWeek::$dayOfMonth::$intervals";
    }

    /**
     * @return bool
     */
    private function issetInterval(): bool
    {
        return !(
            count($this->years) === 0 &&
            count($this->months) === 0 &&
            count($this->daysOfMonth) === 0 &&
            count($this->daysOfWeek) === 0 &&
            count($this->intervals) === 0
        );
    }

    /**
     * How often, in minutes, tasks will be launched (counted from start time of previous launch).
     * If intervals are specified, the task will launch only in them.
     * The task can work in only one mode - either every or a delay.
     * @param int $minutes
     * @throws Exception
     */
    public function every(int $minutes): void
    {
        if ($this->mode === Mode::DELAY) {
            throw new Exception("Cannot set 'every' mode to task because 'delay' mode is already set.");
        }
        if ($minutes < 0) {
            throw new Exception('The number of minutes must be greater than or equal to zero.');
        }
        $this->mode = Mode::EVERY;
        $this->minutes = $minutes;
    }

    /**
     * How many minutes after the previous launch the next one will start (counted from end time of previous launch).
     * If intervals are specified, the task will launch only in them.
     * The task can work in only one mode - either every or a delay.
     * @param int $minutes
     * @throws Exception
     */
    public function delay(int $minutes): void
    {
        if ($this->mode === Mode::EVERY) {
            throw new Exception("Cannot set 'delay' mode to task because 'every' mode is already set.");
        }
        if ($minutes < 0) {
            throw new Exception('The number of minutes must be greater than or equal to zero.');
        }
        $this->mode = Mode::DELAY;
        $this->minutes = $minutes;
    }

    /**
     * Add launch interval. Time must be passed in 'H:i' format (23:59 for example).
     * You can set multiple intervals, but they must not overlap.
     * If every/delay is not specified, the task will be executed once per interval. If given, then according to them.
     * @param string $startTime
     * @param string $endTime
     * @return void
     * @throws Exception
     */
    public function addInterval(string $startTime, string $endTime): void
    {
        $this->validateTimeString($startTime);
        $this->validateTimeString($endTime);
        $carbonStart = Carbon::createFromFormat('H:i', $startTime);
        $carbonEnd = Carbon::createFromFormat('H:i', $endTime);
        if ($carbonStart > $carbonEnd) {
            throw new Exception('The start time must be greater than the end time.');
        }

        $minInterval = $this->config->getMinimumIntervalLength();
        if ($carbonStart->diffInMinutes($carbonEnd) < $minInterval) {
            throw new Exception("Cannot be set interval to less than $minInterval min. Increase the interval " .
                "or change the limit using the setMinimumIntervalLength method of the config. " .
                "ATTENTION: be sure to read the note to the method.");
        }

        foreach ($this->intervals as $int) {
            $intStart = Carbon::createFromFormat('H:i', $int['start']);
            $intEnd = Carbon::createFromFormat('H:i', $int['end']);
            if (
                ($carbonStart >= $intStart && $carbonStart <= $intEnd) ||
                ($carbonEnd >= $intStart && $carbonEnd <= $intEnd) ||
                ($intStart >= $carbonStart && $intStart <= $carbonEnd) ||
                ($intEnd >= $carbonStart && $intEnd <= $carbonEnd)
            ) {
                throw new Exception("The interval '$startTime - $endTime' overlaps with '{$int['start']} - {$int['end']}'.");
            }
        }

        $interval['start'] = $startTime;
        $interval['end'] = $endTime;

        $this->intervals[] = $interval;

        $this->configureSingleMode();
    }

    /**
     * Returns a launch time intervals
     * @return array
     */
    public function getIntervals(): array
    {
        return $this->intervals;
    }

    /**
     * Launch the task only on the specified days of week.
     * Days must be passed by array with int number of day or text representation in any case,
     *  for example: ['Mo', 'TUE', 'wednesday', 4, \Evgeek\Scheduler\Constant\Days::FRIDAY].
     * New call of the method will add new days.
     * @param int|string[] $days
     * @throws Exception
     */
    public function setDaysOfWeek(array $days): void
    {
        foreach ($days as $day) {
            $this->daysOfWeek[Days::number($day)] = true;
        }

        $this->configureSingleMode();
    }

    /**
     * Returns an array with acceptable days of a week
     * @return array
     */
    public function getDaysOfWeek(): array
    {
        return $this->daysOfWeek;
    }

    /**
     * Launch the task only on the specified days of month.
     * Days must be passed by array with int number of day between 1 and 31.
     * New call of the method will add new days.
     * @param int[] $days
     * @throws Exception
     */
    public function setDaysOfMonth(array $days): void
    {
        foreach ($days as $day) {
            if (!is_int($day)) {
                throw new Exception("Days must be an integers.");
            }
            if ($day < 1 || $day > 31) {
                throw new Exception("Days must be between 1 and 31.");
            }
            $this->daysOfMonth[$day] = true;
        }

        $this->configureSingleMode();
    }

    /**
     * Returns an array with acceptable days of a month
     * @return array
     */
    public function getDaysOfMonth(): array
    {
        return $this->daysOfMonth;
    }

    /**
     * Launch the task only on the specified months.
     * Months must be passed by array with int number of months or text representation in any case, for example:
     *  ['January', 'feb', 'MA', 4, \Evgeek\Scheduler\Constant\Month::MAY].
     * New call of the method will add new months.
     * @param array $months
     * @throws Exception
     */
    public function setMonths(array $months): void
    {
        foreach ($months as $month) {
            $this->months[Month::number($month)] = true;
        }

        $this->configureSingleMode();
    }

    /**
     * Returns an array with acceptable month
     * @return array
     */
    public function getMonths(): array
    {
        return $this->months;
    }

    /**
     * Launch the task only on the specified years.
     * Years must be passed by array with int number of year between 1900 and 2999.
     * New call of the method will add new years.
     * @param array $years
     * @throws Exception
     */
    public function setYears(array $years): void
    {
        foreach ($years as $year) {
            if (!is_int($year)) {
                throw new Exception("Years must be an integers.");
            }
            if ($year < 1900 || $year > 2999) {
                throw new Exception("Years must be between 1900 and 2999.");
            }
            $this->years[$year] = true;
        }

        $this->configureSingleMode();
    }

    /**
     * Returns an array with acceptable years
     * @return array
     */
    public function getYears(): array
    {
        return $this->years;
    }

    /**
     * Validate time format. $time must be 'H:i' string.
     * @param string $time
     * @throws Exception
     */
    private function validateTimeString(string $time): void
    {
        $validated = false;
        try {
            $validated = Carbon::createFromFormat('H:i', $time)->toTimeString() === "$time:00";
        } catch (Throwable $e) {
        }

        if (!$validated) {
            throw new Exception("Time '$time' is not valid 'H:i' string.");
        }
    }

    /**
     * If the mode is not EVERY or DELAY, sets the mode to SINGLE.
     * This means that the task should launch single time for interval.
     */
    private function configureSingleMode(): void
    {
        $this->mode = $this->mode === Mode::NONE ? Mode::SINGLE : $this->mode;
    }
}