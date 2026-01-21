<?php

declare(strict_types=1);

namespace App\Entity;

use App\Value\Time\TimeSlotPeriod;

readonly class Shift
{
    public int $stillNeededPeople;

    public function __construct(
        public string $id,
        public TimeSlotPeriod $timeSlotPeriod,
        public ?Location $location,
        public array $assignedPeople,
        public array $team = [],
        public int $totalNeededPeople = 2,
    ) {
        $this->stillNeededPeople = $this->totalNeededPeople - count($this->assignedPeople);
    }
}
