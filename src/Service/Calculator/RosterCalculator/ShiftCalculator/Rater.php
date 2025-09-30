<?php

namespace App\Service\Calculator\RosterCalculator\ShiftCalculator;

use App\Entity\Availability;
use App\Entity\Person;
use App\Entity\Roster;
use App\Entity\Shift;
use App\Service\ResultService;

/**
 * rate a result to be able to compare results
 * 0 points is best.
 */
class Rater
{
    public const POINTS_PER_MISSING_PERSON = 100;
    public const POINTS_PER_MAX_PER_WEEK_EXCEEDED = 10;
    public const POINTS_PER_MAYBE_PERSON = 1;
    public const POINTS_PER_TARGET_SHIFTS_MISSED = 2;

    public function __construct(
        private ResultService $resultService,
    ) {
    }

    public function calculatePoints(array $result, Roster $roster): array
    {
        $points = [
            'notAssigned' => 0,
            'maybeClown' => 0,
            'targetPlays' => 0,
            'maxPerWeek' => 0,
        ];

        $shiftAssignments = $this->resultService->getShiftAssignments($result);
        foreach ($shiftAssignments as $shiftAssignment) {
            $shift = $shiftAssignment['shift'];
            $allAssignedPeople = array_merge($shiftAssignment['addedPeople'], $shift->assignedPeople);
            $points['notAssigned'] += $this->pointsForMissingPerson($allAssignedPeople, $roster);
            $points['maybeClown'] += $this->pointsForMaybePerson($shift, $allAssignedPeople, $roster);
        }

        foreach ($roster->getPeople() as $person) {
            $points['targetPlays'] += $this->pointsForTargetShiftsMissed($person, $result, $roster);
            $points['maxPerWeek'] += $this->pointsForMaxPerWeekExceeded($person, $result, $roster);
        }

        $points['total'] = array_sum($points);

        return $points;
    }

    private function pointsForMissingPerson(array $allAssignedPeople, Roster $roster): int
    {
        $missingPeopleCount = max(0, 2 - count($allAssignedPeople));

        return $missingPeopleCount * $roster->getRating()->pointsPerMissingPerson;
    }

    private function pointsForMaybePerson(Shift $shift, array $allAssignedPeople, Roster $roster): int
    {
        $points = 0;
        foreach ($allAssignedPeople as $person) {
            /** @var Person $person */
            $availability = $person->getAvailabilityOn($shift->timeSlotPeriod);
            if (Availability::MAYBE === $availability) {
                $points += $roster->getRating()->pointsPerMaybePerson;
            }
        }

        return $points;
    }

    private function pointsForTargetShiftsMissed(Person $person, array $result, Roster $roster): int
    {
        $missingShifts = $roster->countShifts() - $this->resultService->countShifts($result);
        $diff = $this->resultService->getCalculatedShifts($result, $person) - $person->targetShifts;

        if ($missingShifts > 0) { // when shifts are missing: only rate people who have too much plays, ignore it when they don't have enough (yet)
            return max(0, $diff) * $roster->getRating()->pointsPerTargetShiftsMissed;
        }

        return abs($diff) * $roster->getRating()->pointsPerTargetShiftsMissed;
    }

    private function pointsForMaxPerWeekExceeded(Person $person, array $result, Roster $roster): int
    {
        if (!$person->maxShiftsPerWeek) {
            return 0;
        }

        $points = 0;

        foreach ($roster->getWeekIds() as $weekId) {
            $shiftsPerWeek = $this->resultService->countShiftsPerWeek($result, $person, $weekId);
            $points += max(0, $shiftsPerWeek - $person->maxShiftsPerWeek) * $roster->getRating()->pointsPerMaxPerWeekExceeded;
        }

        return $points;
    }
}
