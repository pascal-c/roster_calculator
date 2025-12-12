<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Roster;
use App\Service\Calculator\BackTrackingCalculator;
use App\Service\Calculator\SimpleCalculator;

class Calculator
{
    public function __construct(
        private SimpleCalculator $simpleCalculator,
        private BackTrackingCalculator $backTrackingCalculator,
        private ResultService $resultService,
    ) {
    }

    public function calculate(Roster $roster): array
    {
        set_time_limit(BackTrackingCalculator::TIMEOUT_SECONDS + 5);

        $firstResult = $this->simpleCalculator->calculate($roster);
        $bestResult = $this->backTrackingCalculator->calculate(
            $roster,
            $roster->getShifts(),
            $this->resultService->buildEmptyResult($roster),
            $firstResult,
        );

        $this->resultService->setStatistics($bestResult, $this->backTrackingCalculator->isTimedOut(), $this->backTrackingCalculator->counter, $this->resultService->getTotalPoints($firstResult));

        return $bestResult;
    }
}
