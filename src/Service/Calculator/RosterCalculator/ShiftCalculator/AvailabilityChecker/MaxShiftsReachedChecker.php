<?php

namespace App\Service\Calculator\RosterCalculator\ShiftCalculator\AvailabilityChecker;

use App\Entity\Person;
use App\Service\ResultService;

class MaxShiftsReachedChecker
{
    public function __construct(private ResultService $resultService)
    {
    }

    public function maxShiftsPerMonthReached(Person $person, array $result): bool
    {
        return $this->resultService->getCalculatedShifts($result, $person) >= $person->maxShiftsPerMonth;
    }

    public function maxShiftsPerWeekReached(string $weekId, Person $person, array $result): bool
    {
        $softMaxShiftsWeek = $person->maxShiftsPerWeek;
        if (is_null($softMaxShiftsWeek) /* || !$this->configRepository->isFeatureMaxPerWeekActive() */) {
            return false;
        }

        return $this->resultService->countShiftsPerWeek($result, $person, $weekId) >= $softMaxShiftsWeek;
    }

    public function maxShiftsPerDayReached(string $dateIndex, Person $person, array $result): bool
    {
        return $this->resultService->countShiftsPerDay($result, $person, $dateIndex) >= $person->maxShiftsPerDay;
    }
}
