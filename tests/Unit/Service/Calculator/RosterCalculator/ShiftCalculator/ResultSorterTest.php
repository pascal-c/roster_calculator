<?php

declare(strict_types=1);

namespace Tests\Unit\Service\ice\Calculator\RosterCalculator\ShiftCalculator;

use App\Entity\Person;
use App\Entity\Shift;
use App\Service\Calculator\RosterCalculator\ShiftCalculator\ResultSorter;
use App\Service\ResultService;
use Codeception\Stub;
use Codeception\Test\Unit;

final class ResultSorterTest extends Unit
{
    public function testSortForShift(): void
    {
        $shift = Stub::make(Shift::class);
        $person1 = Stub::make(Person::class, ['id' => 'person1']);
        $person2 = Stub::make(Person::class, ['id' => 'person2']);
        $person3 = Stub::make(Person::class, ['id' => 'person3']);
        $results = [['result1'], ['result2'], ['result3']];

        $resultService = $this->createMock(ResultService::class);
        $resultService
            ->method('getOpenTargetShifts')
            ->willReturnCallback(
                function ($result, $person): int {
                    switch ($person->id) {
                        case 'person1':
                            return 2;
                        case 'person2':
                            return 3;
                        case 'person3':
                        default:
                            return 4;
                    }
                }
            );
        $resultService
            ->method('getAddedPeople')
            ->willReturnCallback(
                function ($result, $_shift) use ($person1, $person2, $person3): array {
                    switch ($result[0]) {
                        case 'result1':
                            return [$person1, $person2];
                        case 'result2':
                            return [$person2];
                        case 'result3':
                        default:
                            return [$person2, $person3];
                    }
                }
            );
        $resultService
            ->method('getTotalPoints')
            ->willReturnCallback(
                function ($result): int {
                    switch ($result[0]) {
                        case 'result2':
                            return 10;
                        default:
                            return 5;
                    }
                }
            );

        $sorter = new ResultSorter($resultService);
        $sorter->sortForShift($results, $shift);

        // result3 and result1 have same points, but result3 has more open target shifts (4+3 > 2+3), result2 has more points
        $this->assertSame([['result3'], ['result1'], ['result2']], $results);
    }
}
