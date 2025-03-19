<?php

namespace App\Service;

use App\Entity\Availability;
use App\Entity\Person;
use App\Entity\Roster;
use App\Entity\Shift;

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
            $addedPeople = $shiftAssignment['addedPeople'];
            $points['notAssigned'] += $this->pointsForMissingPerson($addedPeople);
            $points['maybeClown'] += $this->pointsForMaybePerson($shift, $addedPeople);
        }

        foreach ($roster->getPeople() as $person) {
            $points['targetPlays'] += $this->pointsForTargetShiftsMissed($person, $result, $roster);
            $points['maxPerWeek'] += $this->pointsForMaxPerWeekExceeded($person, $result, $roster);
        }

        $points['total'] = array_sum($points);

        return $points;
    }

    private function pointsForMissingPerson(array $addedPeople): int
    {
        return empty($addedPeople) ? static::POINTS_PER_MISSING_PERSON : 0;
    }

    private function pointsForMaybePerson(Shift $shift, array $addedPeople): int
    {
        $points = 0;
        foreach ($addedPeople as $addedPerson) {
            /** @var Person $addedPerson */
            $availability = $addedPerson->getAvailabilityOn($shift->timeSlotPeriod);
            if (Availability::MAYBE === $availability) {
                $points += static::POINTS_PER_MAYBE_PERSON;
            }
        }

        return $points;
    }

    private function pointsForTargetShiftsMissed(Person $person, array $result, Roster $roster): int
    {
        $missingShifts = $roster->countShifts() - $this->resultService->countShifts($result);
        $diff = $this->resultService->getCalculatedShifts($result, $person) - $person->targetShifts;

        if ($missingShifts > 0) { // when shifts are missing: only rate people who have too much plays, ignore it when they don't have enough (yet)
            return max(0, $diff) * static::POINTS_PER_TARGET_SHIFTS_MISSED;
        }

        return abs($diff) * static::POINTS_PER_TARGET_SHIFTS_MISSED;
    }

    private function pointsForMaxPerWeekExceeded(Person $person, array $result, Roster $roster): int
    {
        if (!$person->maxShiftsPerWeek) {
            return 0;
        }

        $points = 0;

        foreach ($roster->getWeekIds() as $weekId) {
            $shiftsPerWeek = $this->resultService->countShiftsPerWeek($result, $person, $weekId);
            $points += max(0, $shiftsPerWeek - $person->maxShiftsPerWeek) * static::POINTS_PER_MAX_PER_WEEK_EXCEEDED;
        }

        return $points;
    }
}
