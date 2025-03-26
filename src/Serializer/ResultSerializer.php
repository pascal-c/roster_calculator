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

    public function serialize(array $result): array
    {
        $serializedResult['assignments'] = $this->serializeAssignemnts($this->resultService->getShiftAssignments($result));
        $serializedResult['personalResults'] = $this->resultService->getAllCalculatedShifts($result);
        $serializedResult['rating'] = $this->resultService->getRating($result);

        return $serializedResult;
    }

    private function serializeAssignemnts(array $resultAssignments): array
    {
        $assignments = [];
        foreach ($resultAssignments as $shiftId => $shiftValues) {
            $assignments[] = [
                'shiftId' => $shiftId,
                'personIds' => array_map(fn (Person $person): string => $person->id, $shiftValues['addedPeople']),
            ];
        }

        return $assignments;
    }
}
