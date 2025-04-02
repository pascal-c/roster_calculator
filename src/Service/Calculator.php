<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Roster;

class Calculator
{
    public function __construct(
        private Assigner $assigner,
        private ResultService $resultService,
    ) {
    }

    public function calculate(Roster $roster): array
    {
        set_time_limit(90);

        $firstResult = $this->assigner->calculateFirst($roster);
        $bestResult = $this->assigner->calculateAll(
            $roster,
            array_reverse($roster->getShifts()),
            $this->resultService->buildEmptyResult($roster),
            $firstResult,
        );

        $this->resultService->setStatistics($bestResult, $this->assigner->isTimedOut(), $this->assigner->counter, $this->resultService->getTotalPoints($firstResult));

        return $bestResult;
    }
}
