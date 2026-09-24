<?php

namespace App\Enums;

enum RepeatTypeEnum: int
{
    case DAILY = 0;
    case WEEKLY = 1;
    case MONTHLY = 2;
    case MONTHLY_RELATIVE = 3;
    case YEARLY = 4;
    case YEARLY_RELATIVE = 5;

    public function translationKey(): string
    {
        return match ($this) {
            self::DAILY => 'option.daily',
            self::WEEKLY => 'option.weekly',
            self::MONTHLY => 'option.montly',
            self::MONTHLY_RELATIVE => 'option.montlyRelative',
            self::YEARLY => 'option.yearly',
            self::YEARLY_RELATIVE => 'option.yearlyRelative',
        };
    }
}
