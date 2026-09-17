<?php

namespace App\Enums;

enum RepeatWeekdayEnum: int
{
    case SUNDAY = 0;
    case MONDAY = 1;
    case TUESDAY = 2;
    case WEDNESDAY = 3;
    case THURSDAY = 4;
    case FRIDAY = 5;
    case SATURDAY = 6;

    public function label(): string
    {
        return match ($this) {
            self::SUNDAY => 'Sunday',
            self::MONDAY => 'Monday',
            self::TUESDAY => 'Tuesday',
            self::WEDNESDAY => 'Wednesday',
            self::THURSDAY => 'Thursday',
            self::FRIDAY => 'Friday',
            self::SATURDAY => 'Saturday',
        };
    }

    public function translationKey(): string
    {
        return match ($this) {
            self::SUNDAY => 'option.sunday',
            self::MONDAY => 'option.monday',
            self::TUESDAY => 'option.tuesday',
            self::WEDNESDAY => 'option.wednesday',
            self::THURSDAY => 'option.thursday',
            self::FRIDAY => 'option.friday',
            self::SATURDAY => 'option.saturday',
        };
    }
}
