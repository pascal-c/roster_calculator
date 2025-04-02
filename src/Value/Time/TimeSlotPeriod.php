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

    public readonly array $timeSlots;
    public readonly string $weekId;
    public readonly string $dateIndex;

    public function __construct(public readonly \DateTimeImmutable $date, public readonly string $daytime)
    {
        if (!in_array($daytime, static::DAYTIMES)) {
            throw new \InvalidArgumentException($daytime.' is not a valid daytime for a '.static::class);
        }

        $this->timeSlots = $this->generateTimeSlots();
        $this->weekId = $this->date->format('o-W');
        $this->dateIndex = $this->date->format('Y-m-d');
    }

    protected function generateTimeSlots(): array
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
