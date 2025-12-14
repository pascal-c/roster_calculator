<?php

namespace App\Service\Calculator\RosterCalculator;

use App\Entity\Person;
use App\Entity\Roster;
use App\Entity\Shift;
use App\Service\ArrayService;
use App\Service\Calculator\RosterCalculator\ShiftCalculator\AvailabilityChecker;

class ShiftSorter
{
    public function __construct(private AvailabilityChecker $availabilityChecker, private ArrayService $arrayService)
    {
    }

    public function sortByAvailabilities(array &$result, array &$shifts, Roster $roster): void
    {
        $this->arrayService->sortBy(
            $shifts,
            fn (Shift $shift): int => count(array_filter(
                $roster->getPeople(),
                fn (Person $person) => $this->availabilityChecker->isAvailableFor($shift, $person, $result)
            )) - $shift->stillNeededPeople
        );
    }
}
