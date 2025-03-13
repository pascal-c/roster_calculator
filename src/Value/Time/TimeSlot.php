<?php

declare(strict_types=1);

namespace App\Value\Time;

class TimeSlot extends TimeSlotPeriod
{
    public const DAYTIMES = [
        self::AM,
        self::PM,
    ];

    protected function generateTimeSlots(): array
    {
        return [$this];
    }
}
