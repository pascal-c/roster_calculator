<?php

namespace App\Service\Calculator;

use App\Entity\Roster;
use App\Service\Calculator\RosterCalculator\ShiftCalculator;
use App\Service\ResultService;

class SimpleCalculator
{
    public function __construct(
        private ResultService $resultService,
        private ShiftCalculator $shiftCalculator,
    ) {
    }

    /**
     * @return array the first result
     */
    public function calculate(Roster $roster): array
    {
        $result = $this->resultService->buildEmptyResult($roster);
        foreach ($roster->getShifts() as $shift) {
            $result = $this->shiftCalculator->calculateSortedResultsForShift($result, $roster, $shift)[0];
        }

        return $result;
    }
}
