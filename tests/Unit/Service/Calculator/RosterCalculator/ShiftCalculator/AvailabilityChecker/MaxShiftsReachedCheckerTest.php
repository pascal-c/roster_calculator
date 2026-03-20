<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Calculator\RosterCalculator\ShiftCalculator\AvailabilityChecker;

use App\Entity\Person;
use App\Service\Calculator\RosterCalculator\ShiftCalculator\AvailabilityChecker\MaxShiftsReachedChecker;
use App\Service\ResultService;
use Codeception\Attribute\DataProvider;
use Codeception\Test\Unit;

final class MaxShiftsReachedCheckerTest extends Unit
{
    #[DataProvider('dayDataProvider')]
    public function testCanTakeNShiftsForDay(int $maxShiftsPerDay, int $n, int $alreadyAssignedShiftsForDay, bool $expectedResult): void
    {
        $day = '2024-07-24';
        $person = $this->make(Person::class, [
            'id' => 'p1',
            'maxShiftsPerDay' => $maxShiftsPerDay,
        ]);
        $result = ['result'];

        $resultService = $this->createMock(ResultService::class);
        $resultService->method('countShiftsPerDay')
            ->with($result, $person, $day)
            ->willReturn($alreadyAssignedShiftsForDay);

        $maxShiftsReachedChecker = new MaxShiftsReachedChecker($resultService);
        $reached = $maxShiftsReachedChecker->canTakeNShiftsForDay($day, $person, $result, n: $n);

        $this->assertSame($expectedResult, $reached);
    }

    public function dayDataProvider(): \Generator
    {
        yield 'with maxShiftsPerDay=1 n=1 alreadyAssignedShiftsForDay=1' => [
            'maxShiftsPerDay' => 1,
            'n' => 1,
            'alreadyAssignedShiftsForDay' => 1,
            'expectedResult' => false,
        ];
        yield 'with maxShiftsPerDay=1 n=1 alreadyAssignedShiftsForDay=0' => [
            'maxShiftsPerDay' => 1,
            'n' => 1,
            'alreadyAssignedShiftsForDay' => 0,
            'expectedResult' => true,
        ];
        yield 'with maxShiftsPerDay=2 n=1 alreadyAssignedShiftsForDay=1' => [
            'maxShiftsPerDay' => 2,
            'n' => 1,
            'alreadyAssignedShiftsForDay' => 1,
            'expectedResult' => true,
        ];
        yield 'with maxShiftsPerDay=2 n=2 alreadyAssignedShiftsForDay=1' => [
            'maxShiftsPerDay' => 2,
            'n' => 2,
            'alreadyAssignedShiftsForDay' => 1,
            'expectedResult' => false,
        ];
        yield 'with maxShiftsPerDay=2 n=2 alreadyAssignedShiftsForDay=0' => [
            'maxShiftsPerDay' => 2,
            'n' => 2,
            'alreadyAssignedShiftsForDay' => 0,
            'expectedResult' => true,
        ];
    }

    #[DataProvider('monthDataProvider')]
    public function testCanTakeNShiftsForMonth(int $maxShiftsPerMonth, int $n, int $alreadyAssignedShiftsForMonth, bool $expectedResult): void
    {
        $person = $this->make(Person::class, [
            'id' => 'p1',
            'maxShiftsPerMonth' => $maxShiftsPerMonth,
        ]);
        $result = ['anything'];

        $resultService = $this->createMock(ResultService::class);
        $resultService->expects($this->once())
            ->method('getCalculatedShifts')
            ->with($result, $person)
            ->willReturn($alreadyAssignedShiftsForMonth);
        $maxShiftsReachedChecker = new MaxShiftsReachedChecker($resultService);

        $reached = $maxShiftsReachedChecker->canTakeNShiftsForMonth($person, $result, n: $n);
        $this->assertSame($expectedResult, $reached);
    }

    public function monthDataProvider(): \Generator
    {
        yield 'with maxShiftsPerMonth=3 n=1 alreadyAssignedShiftsForMonth=2' => [
            'maxShiftsPerMonth' => 3,
            'n' => 1,
            'alreadyAssignedShiftsForMonth' => 2,
            'expectedResult' => true,
        ];
        yield 'with maxShiftsPerMonth=3 n=2 alreadyAssignedShiftsForMonth=2' => [
            'maxShiftsPerMonth' => 3,
            'n' => 2,
            'alreadyAssignedShiftsForMonth' => 2,
            'expectedResult' => false,
        ];
        yield 'with maxShiftsPerMonth=3 n=1 alreadyAssignedShiftsForMonth=3' => [
            'maxShiftsPerMonth' => 3,
            'n' => 1,
            'alreadyAssignedShiftsForMonth' => 3,
            'expectedResult' => false,
        ];
    }
}
