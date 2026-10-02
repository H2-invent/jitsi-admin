<?php

namespace App\Enums;

enum RepeatMonthEnum: int
{
    case JANUARY = 0;
    case FEBRUARY = 1;
    case MARCH = 2;
    case APRIL = 3;
    case MAY = 4;
    case JUNE = 5;
    case JULY = 6;
    case AUGUST = 7;
    case SEPTEMBER = 8;
    case OCTOBER = 9;
    case NOVEMBER = 10;
    case DECEMBER = 11;

    public function label(): string
    {
        return match ($this) {
            self::JANUARY => 'January',
            self::FEBRUARY => 'February',
            self::MARCH => 'March',
            self::APRIL => 'April',
            self::MAY => 'May',
            self::JUNE => 'June',
            self::JULY => 'July',
            self::AUGUST => 'August',
            self::SEPTEMBER => 'September',
            self::OCTOBER => 'October',
            self::NOVEMBER => 'November',
            self::DECEMBER => 'December',
        };
    }

    public function translationKey(): string
    {
        return match ($this) {
            self::JANUARY => 'option.january',
            self::FEBRUARY => 'option.february',
            self::MARCH => 'option.march',
            self::APRIL => 'option.april',
            self::MAY => 'option.may',
            self::JUNE => 'option.june',
            self::JULY => 'option.july',
            self::AUGUST => 'option.august',
            self::SEPTEMBER => 'option.septembre',
            self::OCTOBER => 'option.octobre',
            self::NOVEMBER => 'option.novembre',
            self::DECEMBER => 'option.decembre',
        };
    }
}
