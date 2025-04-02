<?php

namespace App\Service;

use App\Entity\Person;
use App\Entity\Roster;

class Assigner
{
    public $counter = 0;
    private $time = 0;

    public function __construct(
        private ResultService $resultService,
        private AvailabilityChecker $availabilityChecker,
        private PeopleSorter $peopleSorter,
        private Rater $rater,
    ) {
        $this->time = time();
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

    public function calculateAll(Roster $roster, array $shifts, array $currentResult, array $bestResult): array
    {
        ++$this->counter;
        if ($this->isTimedOut()) {
            return $bestResult;
        }

        $shift = array_pop($shifts);

        $availablePeople = array_filter(
            $roster->getPeople(),
            fn (Person $person): bool => $this->availabilityChecker->isAvailableFor($shift, $person, $currentResult)
        );
        $availablePeople[] = null;
        foreach ($this->peopleSorter->sortForShift($shift, $availablePeople, $currentResult) as $person) {
            $newResult = $this->resultService->add($currentResult, $shift, $person);
            $newResultRating = $this->rater->calculatePoints($newResult, $roster);
            if ($newResultRating['total'] < $this->resultService->getTotalPoints($bestResult)) {
                $this->resultService->setRating($newResult, $newResultRating);
                if (empty($shifts)) {
                    return $newResult;
                }
                $bestResult = $this->calculateAll($roster, $shifts, $newResult, $bestResult);
            }
        }

        return $bestResult;
    }

    public function isTimedOut(): bool
    {
        return time() - $this->time > 85;
    }
}
