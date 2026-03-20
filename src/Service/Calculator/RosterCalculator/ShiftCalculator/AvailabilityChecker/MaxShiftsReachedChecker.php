<?php

namespace App\Service\Calculator\RosterCalculator\ShiftCalculator\AvailabilityChecker;

use App\Entity\Person;
use App\Service\ResultService;

class MaxShiftsReachedChecker
{
    public function __construct(private ResultService $resultService)
    {
    }

    public function canTakeNShiftsForMonth(Person $person, array $result, int $n): bool
    {
        return $person->maxShiftsPerMonth - $this->resultService->getCalculatedShifts($result, $person) >= $n;
    }

    public function canTakeNShiftsForDay(string $dateIndex, Person $person, array $result, int $n): bool
    {
        return $person->maxShiftsPerDay - $this->resultService->countShiftsPerDay($result, $person, $dateIndex) >= $n;
    }
}
