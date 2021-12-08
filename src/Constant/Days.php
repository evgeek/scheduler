<?php

namespace Evgeek\Scheduler\Constant;

use Exception;

final class Days
{
    /** @var int */
    public const MONDAY = 1;
    /** @var int */
    public const TUESDAY = 2;
    /** @var int */
    public const WEDNESDAY = 3;
    /** @var int */
    public const THURSDAY = 4;
    /** @var int */
    public const FRIDAY = 5;
    /** @var int */
    public const SATURDAY = 6;
    /** @var int */
    public const SUNDAY = 7;

    /** @var int[] */
    public const VARIANTS = [
        self::MONDAY => self::MONDAY,
        'monday' => self::MONDAY,
        'mo' => self::MONDAY,
        'mon' => self::MONDAY,
        self::TUESDAY => self::TUESDAY,
        'tuesday' => self::TUESDAY,
        'tu' => self::TUESDAY,
        'tue' => self::TUESDAY,
        self::WEDNESDAY => self::WEDNESDAY,
        'wednesday' => self::WEDNESDAY,
        'we' => self::WEDNESDAY,
        'wed' => self::WEDNESDAY,
        self::THURSDAY => self::THURSDAY,
        'thursday' => self::THURSDAY,
        'th' => self::THURSDAY,
        'thu' => self::THURSDAY,
        self::FRIDAY => self::FRIDAY,
        'friday' => self::FRIDAY,
        'fr' => self::FRIDAY,
        'fri' => self::FRIDAY,
        self::SATURDAY => self::SATURDAY,
        'saturday' => self::SATURDAY,
        'sa' => self::SATURDAY,
        'sat' => self::SATURDAY,
        self::SUNDAY => self::SUNDAY,
        'sunday' => self::SUNDAY,
        'su' => self::SUNDAY,
        'sun' => self::SUNDAY,
    ];


    /**
     * Returns the day of the week number from the passed integer/string representation of the day.
     * Throws exception if the representation is invalid.
     * @param $day
     * @return int
     * @throws Exception
     */
    public static function number($day): int
    {
        if (!is_string($day) && !is_int($day)) {
            throw new Exception('Passed day must be int/string type. Type ' . gettype($day) . ' is invalid.');
        }

        $day = is_string($day) ? strtolower($day) : $day;
        $number = self::VARIANTS[$day] ?? null;
        if ($number === null) {
            throw new Exception("$day is not valid day representation. Use 1-7, Monday-Saturday, Mon-Sun or Mo-Su.");
        }
        return self::VARIANTS[$day];
    }
}