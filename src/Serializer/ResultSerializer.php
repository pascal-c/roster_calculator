<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Entity\Person;
use App\Service\ResultService;

class ResultSerializer
{
    public function __construct(private ResultService $resultService)
    {
    }

    public function serializeAssignments(array $result): array
    {
        $assignments = [];
        foreach ($this->resultService->getShiftAssignments($result) as $shiftId => $shiftValues) {
            $assignments[] = [
                'shiftId' => $shiftId,
                'personIds' => array_map(fn (Person $person): string => $person->id, $shiftValues['addedPeople']),
            ];
        }

        return $assignments;
    }
}
