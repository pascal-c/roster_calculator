<?php

declare(strict_types=1);

namespace App\Entity;

use App\Value\Time\TimeSlotPeriod;

class Shift
{
    public function __construct(
        public readonly string $id,
        public readonly TimeSlotPeriod $timeSlotPeriod,
        public readonly ?Location $location,
        public readonly array $assignedPeople,
    ) {
    }
}
