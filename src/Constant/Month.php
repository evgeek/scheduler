<?php

namespace Evgeek\Scheduler\Constant;

use Exception;

final class Month
{
    /** @var int */
    public const JANUARY = 1;
    /** @var int */
    public const FEBRUARY = 2;
    /** @var int */
    public const MARCH = 3;
    /** @var int */
    public const APRIL = 4;
    /** @var int */
    public const MAY = 5;
    /** @var int */
    public const JUNE = 6;
    /** @var int */
    public const JULY = 7;
    /** @var int */
    public const AUGUST = 8;
    /** @var int */
    public const SEPTEMBER = 9;
    /** @var int */
    public const OCTOBER = 10;
    /** @var int */
    public const NOVEMBER = 11;
    /** @var int */
    public const DECEMBER = 12;

    /** @var int[] */
    public const VARIANTS = [
        self::JANUARY => self::JANUARY,
        'january' => self::JANUARY,
        'jan' => self::JANUARY,
        'ja' => self::JANUARY,
        self::FEBRUARY => self::FEBRUARY,
        'february' => self::FEBRUARY,
        'feb' => self::FEBRUARY,
        'fe' => self::FEBRUARY,
        self::MARCH => self::MARCH,
        'march' => self::MARCH,
        'mar' => self::MARCH,
        'ma' => self::MARCH,
        self::APRIL => self::APRIL,
        'april' => self::APRIL,
        'apr' => self::APRIL,
        'ap' => self::APRIL,
        self::MAY => self::MAY,
        'may' => self::MAY,
        self::JUNE => self::JUNE,
        'june' => self::JUNE,
        'jun' => self::JUNE,
        self::JULY => self::JULY,
        'july' => self::JULY,
        'jul' => self::JULY,
        self::AUGUST => self::AUGUST,
        'august' => self::AUGUST,
        'aug' => self::AUGUST,
        'au' => self::AUGUST,
        self::SEPTEMBER => self::SEPTEMBER,
        'september' => self::SEPTEMBER,
        'sept' => self::SEPTEMBER,
        'sep' => self::SEPTEMBER,
        self::OCTOBER => self::OCTOBER,
        'october' => self::OCTOBER,
        'oct' => self::OCTOBER,
        'oc' => self::OCTOBER,
        self::NOVEMBER => self::NOVEMBER,
        'november' => self::NOVEMBER,
        'nov' => self::NOVEMBER,
        'no' => self::NOVEMBER,
        self::DECEMBER => self::DECEMBER,
        'december' => self::DECEMBER,
        'dec' => self::DECEMBER,
        'de' => self::DECEMBER,
    ];

    /**
     * Returns the month number from the passed integer/string representation of the month.
     * Throws exception if the representation is invalid.
     * @param $month
     * @return int
     * @throws Exception
     */
    public static function number($month): int
    {
        if (!is_string($month) && !is_int($month)) {
            throw new Exception('Passed month must be int/string type. Type ' . gettype($month) . ' is invalid.');
        }

        $month = is_string($month) ? strtolower($month) : $month;
        $number = self::VARIANTS[$month] ?? null;
        if ($number === null) {
            throw new Exception("$month is not valid month representation. Use 1-12, January-December, Jan-Dec or Ja-De.");
        }
        return self::VARIANTS[$month];
    }
}
