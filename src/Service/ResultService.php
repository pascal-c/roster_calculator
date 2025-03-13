<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Person;
use App\Entity\Roster;
use App\Entity\Shift;
use App\Value\Time\TimeSlot;

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
                foreach ($shift->timeSlotPeriod->timeSlots as $timeSlot) {
                    $result['people'][$person->id]['timeSlots'][$timeSlot->dateIndex][$timeSlot->daytime] = $timeSlot;
                }
                ++$result['people'][$person->id]['calculatedShifts'];
            }
        }

        return $result;
    }

    public function add(array $result, Shift $shift, Person $person): array
    {
        if (array_key_exists($shift->id, $result['shifts'])) {
            $result['shifts'][$shift->id]['addedPeople'][] = $person;
        } else {
            $result['shifts'][$shift->id] = [
                'shift' => $shift,
                'addedPeople' => [$person],
            ];
        }

        ++$result['people'][$person->id]['calculatedShifts'];
        foreach ($shift->timeSlotPeriod->timeSlots as $timeSlot) {
            $result['people'][$person->id]['timeSlots'][$timeSlot->dateIndex][$timeSlot->daytime] = $timeSlot;
        }

        return $result;
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

    public function isAssignedAtTimeSlot(array $result, Person $person, TimeSlot $timeSlot): bool
    {
        return isset($result['people'][$person->id]['timeSlots'][$timeSlot->dateIndex][$timeSlot->daytime]);
    }
}
