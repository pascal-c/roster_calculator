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
            'missingPerson' => 0,
            'maybePerson' => 0,
            'targetShifts' => 0,
            'maxPerWeek' => 0,
            'locationPreferences' => 0,
            'personNotInTeam' => 0,
        ];

        $shiftAssignments = $this->resultService->getShiftAssignments($result);
        foreach ($shiftAssignments as $shiftAssignment) {
            $shift = $shiftAssignment['shift'];
            $allAssignedPeople = array_merge($shiftAssignment['addedPeople'], $shift->assignedPeople);
            $points['missingPerson'] += $this->pointsForMissingPerson($allAssignedPeople, $roster);
            $points['maybePerson'] += $this->pointsForMaybePerson($shift, $allAssignedPeople, $roster);
            $points['locationPreferences'] += $this->pointsForLocationPreferences($shift, $allAssignedPeople);
            $points['personNotInTeam'] += $this->pointsForPersonNotInTeam($shift, $allAssignedPeople, $roster);
        }

        foreach ($roster->getPeople() as $person) {
            $points['targetShifts'] += $this->pointsForTargetShiftsMissed($person, $result, $roster);
            $points['maxPerWeek'] += $this->pointsForMaxPerWeekExceeded($person, $result, $roster);
        }

        $points['total'] = array_sum($points);

        return $points;
    }

    private function pointsForMissingPerson(array $allAssignedPeople, Roster $roster): int
    {
        $missingPeopleCount = max(0, 2 - count($allAssignedPeople));

        return $missingPeopleCount * $roster->getRatingPointWeightings()->pointsPerMissingPerson;
    }

    private function pointsForMaybePerson(Shift $shift, array $allAssignedPeople, Roster $roster): int
    {
        $points = 0;
        foreach ($allAssignedPeople as $person) {
            /** @var Person $person */
            $availability = $person->getAvailabilityOn($shift->timeSlotPeriod);
            if (Availability::MAYBE === $availability) {
                $points += $roster->getRatingPointWeightings()->pointsPerMaybePerson;
            }
        }

        return $points;
    }

    private function pointsForLocationPreferences(Shift $shift, array $allAssignedPeople): int
    {
        $points = 0;
        foreach ($allAssignedPeople as $person) {
            /** @var Person $person */
            $locationPreference = $person->getLocationPreferenceFor($shift->location);
            $points += $locationPreference->points;
        }

        return $points;
    }

    private function pointsForPersonNotInTeam(Shift $shift, array $allAssignedPeople, Roster $roster): int
    {
        if (0 === count($shift->team)) {
            return 0;
        }

        $points = 0;
        foreach ($allAssignedPeople as $person) {
            /** @var Person $person */
            if (!in_array($person, $shift->team, true)) {
                $points += $roster->getRatingPointWeightings()->pointsPerPersonNotInTeam;
            }
        }

        return $points;
    }

    private function pointsForTargetShiftsMissed(Person $person, array $result, Roster $roster): int
    {
        $missingShifts = $roster->countShifts() - $this->resultService->countShifts($result);
        $diff = $this->resultService->getCalculatedShifts($result, $person) - $person->targetShifts;
        $points = $diff * $diff * $roster->getRatingPointWeightings()->pointsPerTargetShiftsMissed;

        if ($missingShifts > 0 && $diff <= 0) { // when shifts are missing: do not rate people who don't have enough shifts (yet) ...
            return 0;
        } elseif ($missingShifts > 0) { // ... but add additional rating points when the have too much plays -> others will have less at the end!
            return $points + $diff * $roster->getRatingPointWeightings()->pointsPerTargetShiftsMissed;
        }

        return $points;
    }

    private function pointsForMaxPerWeekExceeded(Person $person, array $result, Roster $roster): int
    {
        if (!$person->maxShiftsPerWeek) {
            return 0;
        }

        $points = 0;

        foreach ($roster->getWeekIds() as $weekId) {
            $shiftsPerWeek = $this->resultService->countShiftsPerWeek($result, $person, $weekId);
            $points += max(0, $shiftsPerWeek - $person->maxShiftsPerWeek) * $roster->getRatingPointWeightings()->pointsPerMaxPerWeekExceeded;
        }

        return $points;
    }
}
