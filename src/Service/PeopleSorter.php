<?php

namespace App\Service;

use App\Entity\Availability;
use App\Entity\Person;
use App\Entity\Shift;

class PeopleSorter
{
    public function __construct(
        private MaxShiftsReachedChecker $maxShiftsReachedChecker,
        private ResultService $resultService,
    ) {
    }

    /**
     * @param array<Person> $people
     * @param array         $result the current result
     *
     * @return array<Person> the sorted people
     */
    public function sortForShift(Shift $shift, array $people, array $result): array
    {
        usort(
            $people,
            function (?Person $person1, ?Person $person2) use ($shift, $result) {
                // if person ist null it comes last
                if (is_null($person1)) {
                    return is_null($person2) ? 0 : 1;
                }
                if (is_null($person2)) {
                    return -1;
                }

                // when maxShiftsWeek ist reached, the person comes last
                $person1MaxShiftsWeekReached = $this->maxShiftsReachedChecker->maxShiftsPerWeekReached($shift->timeSlotPeriod->weekId, $person1, $result);
                $person2MaxShiftsWeekReached = $this->maxShiftsReachedChecker->maxShiftsPerWeekReached($shift->timeSlotPeriod->weekId, $person2, $result);
                if ($person1MaxShiftsWeekReached !== $person2MaxShiftsWeekReached) {
                    return $person1MaxShiftsWeekReached ? 1 : -1;
                }

                // when availability is the same, take person with more open plays first
                $person1Availability = $person1->getAvailabilityOn($shift->timeSlotPeriod);
                $person2Availability = $person2->getAvailabilityOn($shift->timeSlotPeriod);
                if ($person1Availability == $person2Availability) {
                    return
                        $this->resultService->getOpenTargetShifts($result, $person2)
                        <=>
                        $this->resultService->getOpenTargetShifts($result, $person1);
                }

                // take available person with 'yes' before person with 'maybe'
                return Availability::YES == $person1Availability ? -1 : 1;
            }
        );

        return $people;
    }
}
