<?php

declare(strict_types=1);

namespace App\Value\Time;

class TimeSlotPeriod
{
    public const ALL = 'all';
    public const AM = 'am';
    public const PM = 'pm';

    public const DAYTIMES = [
        self::ALL,
        self::AM,
        self::PM,
    ];

    public function __construct(public readonly \DateTimeImmutable $date, public readonly string $daytime)
    {
        if (!in_array($daytime, static::DAYTIMES)) {
            throw new \InvalidArgumentException($daytime.' is not a valid daytime for a '.static::class);
        }
    }

    public function getTimeSlots(): array
    {
        if (self::ALL === $this->daytime) {
            return [
                new TimeSlot($this->date, self::AM),
                new TimeSlot($this->date, self::PM),
            ];
        }

        return [new TimeSlot($this->date, $this->daytime)];
    }
}
