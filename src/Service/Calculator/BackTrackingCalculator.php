<?php

namespace App\Service\Calculator;

use App\Entity\Roster;
use App\Service\Calculator\RosterCalculator\ShiftCalculator;
use App\Service\ResultService;
use App\Service\TimeService;

class BackTrackingCalculator
{
    public int $counter = 0;
    private int $time;

    public function __construct(
        private ResultService $resultService,
        private ShiftCalculator $shiftCalculator,
        private TimeService $timeService,
    ) {
        $this->time = $this->timeService->unixTimestamp();
    }

    public function calculate(Roster $roster, array $shifts, array $currentResult, array $bestResult): array
    {
        ++$this->counter;
        if ($this->isTimedOut()) {
            return $bestResult;
        }

        $shift = array_pop($shifts);

        $newResults = $this->shiftCalculator->calculateSortedResultsForShift($currentResult, $roster, $shift);
        foreach ($newResults as $newResult) {
            if ($this->resultService->getTotalPoints($newResult) < $this->resultService->getTotalPoints($bestResult)) {
                if (empty($shifts)) {
                    return $newResult;
                }
                $bestResult = $this->calculate($roster, $shifts, $newResult, $bestResult);
            } elseif (empty($shifts)) {
                return $bestResult;
            }
        }

        return $bestResult;
    }

    public function isTimedOut(): bool
    {
        return $this->timeService->unixTimestamp() - $this->time > 85;
    }
}
