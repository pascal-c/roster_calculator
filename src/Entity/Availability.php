<?php

declare(strict_types=1);

namespace App\Entity;

use App\Value\Time\TimeSlot;

class Availability
{
    public const MAYBE = 'maybe';
    public const NO = 'no';
    public const YES = 'yes';

    public function __construct(
        public readonly TimeSlot $timeSlot,
        public readonly string $availability,
    ) {
    }
}
