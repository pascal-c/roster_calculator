<?php

declare(strict_types=1);

namespace App\Entity;

use App\Value\Time\TimeSlotPeriod;

class Shift
{
    public readonly int $stillNeededPeople;
    /** @var array<Shift> */
    public array $bundledShifts = [];

    public function __construct(
        public readonly string $id,
        public readonly TimeSlotPeriod $timeSlotPeriod,
        public readonly ?Location $location,
        public readonly array $assignedPeople,
        public readonly array $team = [],
        public readonly int $totalNeededPeople = 2,
        public readonly ?string $bundleId = null,
    ) {
        $this->stillNeededPeople = $this->totalNeededPeople - count($this->assignedPeople);
    }
}
