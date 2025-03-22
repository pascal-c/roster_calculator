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
        $serializedResult['assignments'] = array_map(fn (array $assignment): array => array_map(fn (Person $person): string => $person->id, $assignment['addedPeople']), $this->resultService->getShiftAssignments($result));
        $serializedResult['calculatedShifts'] = $this->resultService->getAllCalculatedShifts($result);
        $serializedResult['rating'] = $this->resultService->getRating($result);

        return $serializedResult;
    }
}
