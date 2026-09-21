<?php

namespace App\Enums;

enum RepeatNumberEnum: int
{
    case FIRST = 0;
    case SECOND = 1;
    case THIRD = 2;
    case FOURTH = 3;
    case FIFTH = 4;
    case LAST = 5;

    public function label(): string
    {
        return match ($this) {
            self::FIRST => 'First',
            self::SECOND => 'Second',
            self::THIRD => 'Third',
            self::FOURTH => 'Fourth',
            self::FIFTH => 'Fifth',
            self::LAST => 'Last',
        };
    }

    public function translationKey(): string
    {
        return match ($this) {
            self::FIRST => 'option.first',
            self::SECOND => 'option.second',
            self::THIRD => 'option.third',
            self::FOURTH => 'option.fourth',
            self::FIFTH => 'option.fifth',
            self::LAST => 'option.last',
        };
    }
}
