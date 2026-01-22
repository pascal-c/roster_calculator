<?php

declare(strict_types=1);

namespace App\Entity;

class RatingPointWeightings
{
    public function __construct(
        public readonly int $pointsPerMissingPerson = 100,
        public readonly int $pointsPerMaxPerWeekExceeded = 10,
        public readonly int $pointsPerMaybePerson = 1,
        public readonly int $pointsPerTargetShiftsMissed = 2,
        public readonly int $pointsPerPersonNotInTeam = 3,
    ) {
    }
}
