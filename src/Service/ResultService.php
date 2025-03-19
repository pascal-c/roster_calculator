<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Person;
use App\Entity\Roster;
use App\Entity\Shift;
use App\Value\Time\TimeSlot;
use App\Value\Time\TimeSlotPeriod;

class ResultService
{
    public function buildEmptyResult(Roster $roster): array
    {
        $result = ['shifts' => [], 'people' => []];

        foreach ($roster->getPeople() as $person) {
            $result['people'][$person->id] = [
                'person' => $person,
                'calculatedShifts' => 0,
                'timeSlots' => [],
            ];
        }
        foreach ($roster->getShifts() as $shift) {
            /** @var Shift $shift */
            foreach ($shift->assignedPeople as $person) {
                $result['people'][$person->id]['timeSlots'][$shift->timeSlotPeriod->dateIndex][$shift->timeSlotPeriod->daytime] = $shift->timeSlotPeriod;
                ++$result['people'][$person->id]['calculatedShifts'];
            }
        }

        return $result;
    }

    public function add(array $result, Shift $shift, ?Person $person): array
    {
        if (!array_key_exists($shift->id, $result['shifts'])) {
            $result['shifts'][$shift->id] = [
                'shift' => $shift,
                'addedPeople' => [],
            ];
        }
        if (is_null($person)) {
            return $result;
        }

        $result['shifts'][$shift->id]['addedPeople'][] = $person;

        $result['people'][$person->id]['timeSlots'][$shift->timeSlotPeriod->dateIndex][$shift->timeSlotPeriod->daytime] = $shift->timeSlotPeriod;
        ++$result['people'][$person->id]['calculatedShifts'];

        return $result;
    }

    public function getShiftAssignments(array $result): array
    {
        return $result['shifts'];
    }

    public function getShifts(array $result): array
    {
        return array_values(array_map(fn (array $shiftAssignemnt): Shift => $shiftAssignemnt['shift'], $this->getShiftAssignments($result)));
    }

    public function countShifts(array $result): int
    {
        return count($result['shifts']);
    }

    public function getAddedPeople(array $result, Shift $shift): array
    {
        return $result['shifts'][$shift->id]['addedPeople'] ?? [];
    }

    public function countShiftsPerDay(array $result, Person $person, string $dateIndex): int
    {
        $timeSlotsByDay = $result['people'][$person->id]['timeSlots'][$dateIndex] ?? [];

        return count($timeSlotsByDay);
    }

    public function countShiftsPerWeek(array $result, Person $person, string $weekId): int
    {
        $count = 0;
        foreach ($result['people'][$person->id]['timeSlots'] as $timeSlotsByDay) {
            foreach ($timeSlotsByDay as $timeSlot) {
                if ($timeSlot->weekId === $weekId) {
                    ++$count;
                }
            }
        }

        return $count;
    }

    public function getCalculatedShifts(array $result, Person $person): int
    {
        return $result['people'][$person->id]['calculatedShifts'];
    }

    public function getOpenTargetShifts(array $result, Person $person): int
    {
        return $person->targetShifts - $result['people'][$person->id]['calculatedShifts'];
    }

    public function isAssignedAtTimeSlot(array $result, Person $person, TimeSlot $timeSlot): bool
    {
        return isset($result['people'][$person->id]['timeSlots'][$timeSlot->dateIndex][$timeSlot->daytime])
            || isset($result['people'][$person->id]['timeSlots'][$timeSlot->dateIndex][TimeSlotPeriod::ALL])
        ;
    }

    public function setRating(array &$result, array $rating): void
    {
        $result['rating'] = $rating;
    }

    public function getRating(array &$result): array
    {
        return $result['rating'] ?? [];
    }

    public function getTotalPoints(array &$result): int
    {
        return $result['rating']['total'];
    }
}
