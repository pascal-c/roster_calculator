<?php

namespace App\Service;

use App\Entity\Person;
use App\Entity\Roster;
use App\Entity\Shift;

class Assigner
{
    public function __construct(
        private ResultService $resultService,
        private AvailabilityChecker $availabilityChecker,
        private PeopleSorter $peopleSorter,
        private Rater $rater,
    ) {
    }

    /**
     * @return array the first result
     */
    public function calculateFirst(Roster $roster): array
    {
        $result = $this->resultService->buildEmptyResult($roster);
        foreach ($roster->getShifts() as $shift) {
            $availablePeople = array_filter(
                $roster->getPeople(),
                fn (Person $person): bool => $this->availabilityChecker->isAvailableFor($shift, $person, $result)
            );
            $person = $this->peopleSorter->sortForShift($shift, $availablePeople, $result)[0] ?? null;

            $result = $this->resultService->add($result, $shift, $person);
        }

        $rating = $this->rater->calculatePoints($result, $roster);
        $this->resultService->setRating($result, $rating);

        return $result;
    }

    public function calculateAll(Roster $roster, array $shifts, int $bestResultTotalPoints): array
    {
        if (empty($shifts)) {
            return [$this->resultService->buildEmptyResult($roster)];
        }

        $shift = array_pop($shifts);

        return $this->addShift(
            $roster,
            $shift,
            $bestResultTotalPoints,
            $this->calculateAll($roster, $shifts, $bestResultTotalPoints),
        );
    }

    private function addShift(Roster $roster, Shift $shift, int $bestResultTotalPoints, array $results): array
    {
        $newResults = [];

        foreach ($results as $result) {
            $availablePeople = array_filter(
                $roster->getPeople(),
                fn (Person $person): bool => $this->availabilityChecker->isAvailableFor($shift, $person, $result)
            );
            foreach ($this->peopleSorter->sortForShift($shift, $availablePeople, $result) as $person) {
                $newResult = $this->resultService->add($result, $shift, $person);
                $newResultRating = $this->rater->calculatePoints($newResult, $roster);
                if ($newResultRating['total'] < $bestResultTotalPoints) {
                    $this->resultService->setRating($newResult, $newResultRating);
                    $newResults[] = $newResult;
                }
            }

            // try with empty person as well
            $newResult = $this->resultService->add($result, $shift, null);
            $newResultRating = $this->rater->calculatePoints($newResult, $roster);
            if ($newResultRating['total'] < $bestResultTotalPoints) {
                $this->resultService->setRating($newResult, $newResultRating);
                $newResults[] = $newResult;
            }
        }

        return $newResults;
    }
}
