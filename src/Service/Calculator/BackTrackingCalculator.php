<?php

namespace App\Service\Calculator;

use App\Entity\Roster;
use App\Service\Calculator\RosterCalculator\ShiftCalculator;
use App\Service\Calculator\RosterCalculator\ShiftSorter;
use App\Service\ResultService;
use App\Service\TimeService;

class BackTrackingCalculator
{
    public const TIMEOUT_SECONDS = 85;
    public int $counter = 0;
    private int $time;

    public function __construct(
        private ResultService $resultService,
        private ShiftCalculator $shiftCalculator,
        private TimeService $timeService,
        private ShiftSorter $shiftSorter,
    ) {
        $this->time = $this->timeService->unixTimestamp();
    }

    public function calculate(Roster $roster, array $shifts, array $currentResult, array $bestResult): array
    {
        ++$this->counter;
        if ($this->isTimedOut()) {
            return $bestResult;
        }

        $this->shiftSorter->sortByAvailabilities($currentResult, $shifts, $roster);
        $shift = array_shift($shifts);

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
        return $this->timeService->unixTimestamp() - $this->time > self::TIMEOUT_SECONDS;
    }
}
