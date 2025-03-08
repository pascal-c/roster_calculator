<?php

declare(strict_types=1);

namespace App\Value\Time;

class TimeSlot extends TimeSlotPeriod
{
    public const DAYTIMES = [
        self::AM,
        self::PM,
    ];

    public function getIndex(): string
    {
        return $this->date->format('Y-m-d ').$this->daytime;
    }
}
