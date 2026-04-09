<?php

namespace App\Service\Calculator\RosterCalculator\ShiftCalculator;

use App\Entity\Location;
use App\Entity\Person;
use App\Entity\Shift;
use App\Service\Calculator\RosterCalculator\ShiftCalculator\AvailabilityChecker\MaxShiftsReachedChecker;
use App\Service\ResultService;
use App\Value\Time\TimeSlotPeriod;

class AvailabilityChecker
{
    public function __construct(
        private MaxShiftsReachedChecker $maxShiftsReachedChecker,
        private ResultService $resultService,
    ) {
    }

    public function isAvailableFor(Shift $shift, Person $person, array $result): bool
    {
        $shifts = [$shift];
        foreach ($shift->bundledShifts as $bundledShift) {
            $shifts[] = $bundledShift;
        }

        $shiftPerMonthCount = count($shifts);
        $dates = array_map(fn (Shift $shift) => $shift->timeSlotPeriod->dateIndex, $shifts);
        $uniqueDates = array_unique($dates);
        $shiftPerDayCount = count($uniqueDates) !== count($dates) ? 2 : 1;

        return array_reduce($shifts, fn (bool $carry, Shift $shift) => $carry && $this->isAvailableForOne($shift, $person, $result, $shiftPerMonthCount, $shiftPerDayCount), true);
    }

    /**
     * should be private but is public for testing purposes.
     */
    public function isAvailableForOne(Shift $shift, Person $person, array $result, int $shiftPerMonthCount, int $shiftPerDayCount): bool
    {
        return
            $person->isAvailableOn($shift->timeSlotPeriod)
            && !$this->isAlreadyAssignedWithin($result, $person, $shift->timeSlotPeriod)
            && !$this->isBlockedForLocation($shift->location, $person)
            && !$this->isBlockedForAPerson($this->resultService->getAllAssignedPeople($result, $shift), $person)
            && $this->maxShiftsReachedChecker->canTakeNShiftsForMonth($person, $result, n: $shiftPerMonthCount)
            && $this->maxShiftsReachedChecker->canTakeNShiftsForDay($shift->timeSlotPeriod->dateIndex, $person, $result, n: $shiftPerDayCount)
            && !$this->onlyMen($result, $shift, $person)
        ;
    }

    public function onlyMen(array $result, Shift $shift, Person $person): bool
    {
        if (!$person->gender->isMale()) {
            return false;
        }

        foreach ($shift->assignedPeople as $assignedPerson) {
            if ($assignedPerson->gender->isMale()) {
                return true;
            }
        }

        foreach ($this->resultService->getAddedPeople($result, $shift) as $addedPerson) {
            if ($addedPerson->gender->isMale()) {
                return true;
            }
        }

        return false;
    }

    public function isAlreadyAssignedWithin(array $result, Person $person, TimeSlotPeriod $timeSlotPeriod): bool
    {
        foreach ($timeSlotPeriod->timeSlots as $timeSlot) {
            if ($this->resultService->isAssignedAtTimeSlot($result, $person, $timeSlot)) {
                return true;
            }
        }

        return false;
    }

    public function isBlockedForLocation(?Location $location, Person $person): bool
    {
        if (is_null($location)) {
            return false;
        }

        return in_array($person, $location->blockedPeople, true);
    }

    public function isBlockedForAPerson(array $people, Person $personToCheck): bool
    {
        foreach ($people as $person) {
            /** @var Person $person */
            if ($person->isBlocked($personToCheck) || $personToCheck->isBlocked($person)) {
                return true;
            }
        }

        return false;
    }
}
