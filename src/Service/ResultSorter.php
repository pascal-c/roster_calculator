<?php

namespace App\Service;

use App\Entity\Shift;

class ResultSorter
{
    public function __construct(private ResultService $resultService)
    {
    }

    public function sortForShift(array &$results, Shift $shift): void
    {
        usort(
            $results,
            function (array $result1, array $result2) use ($shift): int {
                $result1Points = $this->resultService->getTotalPoints($result1);
                $result2Points = $this->resultService->getTotalPoints($result2);
                if ($result1Points === $result2Points) { // when rating is the same, take shift with more open plays first
                    return
                        $this->getTotalOpenTargetShifts($result2, $shift)
                        <=>
                        $this->getTotalOpenTargetShifts($result1, $shift);
                }

                return $result1Points <=> $result2Points;
            }
        );
    }

    private function getTotalOpenTargetShifts(array $result, Shift $shift): int
    {
        $total = 0;
        foreach ($this->resultService->getAddedPeople($result, $shift) as $person) {
            $total += $this->resultService->getOpenTargetShifts($result, $person);
        }

        return $total;
    }
}
