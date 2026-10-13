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

    public function calculate(Roster $roster, int $calculationTime = BackTrackingCalculator::DEFAULT_CALCULATION_TIME): array
    {
        set_time_limit($calculationTime + 5);

        $firstResult = $this->simpleCalculator->calculate($roster);
        $this->backTrackingCalculator->setCalculationTime($calculationTime);
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
