<?php

namespace App\Service;

use App\Entity\Location;
use App\Entity\Person;
use App\Entity\Shift;
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
        return
            $person->isAvailableOn($shift->timeSlotPeriod)
            && !$this->isAlreadyAssignedWithin($result, $person, $shift->timeSlotPeriod)
            && !$this->isBlocked($shift->location, $person)
            && !$this->maxShiftsReachedChecker->maxShiftsPerMonthReached($person, $result)
            && !$this->maxShiftsReachedChecker->maxShiftsPerDayReached($shift->timeSlotPeriod->dateIndex, $person, $result)
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

    public function isBlocked(?Location $location, Person $person): bool
    {
        if (is_null($location)) {
            return false;
        }

        return in_array($person, $location->blockedPeople);
    }
}
