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
        $firstResult = $this->assigner->calculateFirst($roster);

        $results = $this->assigner->calculateAll($roster, $roster->getShifts(), $this->resultService->getTotalPoints($firstResult));

        $bestResult = array_reduce(
            $results,
            fn (array $carry, array $element): array => $this->resultService->getTotalPoints($element) < $this->resultService->getTotalPoints($carry) ? $element : $carry,
            $firstResult,
        );

        return $bestResult;
    }
}
