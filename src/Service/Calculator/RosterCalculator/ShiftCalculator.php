<?php

namespace App\Service\Calculator\RosterCalculator;

use App\Entity\Person;
use App\Entity\Roster;
use App\Entity\Shift;
use App\Service\Calculator\RosterCalculator\ShiftCalculator\AvailabilityChecker;
use App\Service\Calculator\RosterCalculator\ShiftCalculator\Rater;
use App\Service\Calculator\RosterCalculator\ShiftCalculator\ResultSorter;
use App\Service\ResultService;

class ShiftCalculator
{
    public function __construct(
        private ResultService $resultService,
        private AvailabilityChecker $availabilityChecker,
        private ResultSorter $resultSorter,
        private Rater $rater,
    ) {
    }

    public function calculateSortedResultsForShift(array $result, Roster $roster, Shift $shift): array
    {
        $availablePeople = $this->filterAvailablePeople($shift, $roster->getPeople(), $result);
        $allResults = [$this->add($result, $roster, $shift, null)];
        $this->calculateResultsForNPeople($allResults, $shift->stillNeededPeople, $availablePeople, $roster, $shift);
        $this->resultSorter->sortForShift($allResults, $shift);

        return $allResults;
    }

    private function calculateResultsForNPeople(array &$results, int $stillNeededPeople, array $availablePeople, Roster $roster, Shift $shift): void
    {
        if (0 === $stillNeededPeople) {
            return;
        }

        foreach ($results as $result) {
            $newResults = [];
            foreach ($this->filterAvailablePeople($shift, $availablePeople, $result) as $person) {
                $newResults[] = $this->add($result, $roster, $shift, $person);
            }
            $this->calculateResultsForNPeople($newResults, $stillNeededPeople - 1, $availablePeople, $roster, $shift);

            $results = array_merge($results, $newResults);
        }
    }

    private function filterAvailablePeople(Shift $shift, array $people, array $result): array
    {
        $lastPersonId = $this->resultService->getLastAddedPerson($result, $shift)?->id;
        $personFound = is_null($lastPersonId);
        $availablePeople = [];
        foreach ($people as $personId => $person) {
            if ($personId == $lastPersonId) {
                $personFound = true;
                continue;
            } elseif (!$personFound) {
                continue;
            }
            if ($this->availabilityChecker->isAvailableFor($shift, $person, $result)) {
                $availablePeople[$personId] = $person;
            }
        }

        return $availablePeople;
    }

    private function add(array $result, Roster $roster, Shift $shift, ?Person $person): array
    {
        $newResult = $this->resultService->add($result, $shift, $person);
        $this->resultService->setRating($newResult, $this->rater->calculatePoints($newResult, $roster));

        return $newResult;
    }
}
