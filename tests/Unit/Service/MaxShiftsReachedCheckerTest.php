<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Entity\Person;
use App\Service\MaxShiftsReachedChecker;
use App\Service\ResultService;
use Codeception\Attribute\DataProvider;
use Codeception\Test\Unit;

final class MaxShiftsReachedCheckerTest extends Unit
{
    #[DataProvider('dayDataProvider')]
    public function testMaxShiftsPerDayReached(int $maxShiftsPerDay, bool $expectedResult): void
    {
        $day = '2024-07-24';
        $person = $this->make(Person::class, [
            'id' => 'p1',
            'maxShiftsPerDay' => $maxShiftsPerDay,
        ]);
        $result = ['anything'];

        $resultService = $this->createMock(ResultService::class);
        $resultService->method('countShiftsPerDay')
            ->with($result, $person, $day)
            ->willReturn(1);

        $maxShiftsReachedChecker = new MaxShiftsReachedChecker($resultService);
        $reached = $maxShiftsReachedChecker->maxShiftsPerDayReached($day, $person, $result);

        $this->assertSame($expectedResult, $reached);
    }

    public function dayDataProvider(): \Generator
    {
        yield 'when person has 1 maxShiftsPerDay' => [
            'maxShiftsPerDay' => 1,
            'expectedResult' => true,
        ];
        yield 'when person has 2 maxShiftsPerDay' => [
            'maxShiftsPerDay' => 2,
            'expectedResult' => false,
        ];
    }

    #[DataProvider('weekDataProvider')]
    public function testMaxShiftsPerWeekReached(?int $maxShiftsPerWeek, bool $expectedResult): void
    {
        $weekId = '2024-07';
        $person = $this->make(Person::class, [
            'id' => 'p1',
            'maxShiftsPerWeek' => $maxShiftsPerWeek,
        ]);
        $result = ['anything'];

        $resultService = $this->createMock(ResultService::class);
        $resultService->method('countShiftsPerWeek')
            ->with($result, $person, $weekId)
            ->willReturn(2);
        $maxShiftsReachedChecker = new MaxShiftsReachedChecker($resultService);

        $reached = $maxShiftsReachedChecker->maxShiftsPerWeekReached($weekId, $person, $result);
        $this->assertSame($expectedResult, $reached);
    }

    public function weekDataProvider(): \Generator
    {
        yield 'when person has no max given' => [
            'maxShiftsPerWeek' => null,
            'expectedResult' => false,
        ];
        yield 'when person has 3 maxShiftsPerWeek' => [
            'maxShiftsPerWeek' => 3,
            'expectedResult' => false,
        ];
        yield 'when person has 2 maxShiftsPerWeek' => [
            'maxShiftsPerWeek' => 2,
            'expectedResult' => true,
        ];
        yield 'when person has 1 maxShiftsPerWeek' => [
            'maxShiftsPerWeek' => 1,
            'expectedResult' => true,
        ];
    }

    #[DataProvider('monthDataProvider')]
    public function testMaxShiftsPerMonthReached(int $maxShiftsPerMonth, bool $expectedResult): void
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
            ->willReturn(2);
        $maxShiftsReachedChecker = new MaxShiftsReachedChecker($resultService);

        $reached = $maxShiftsReachedChecker->maxShiftsPerMonthReached($person, $result);
        $this->assertSame($expectedResult, $reached);
    }

    public function monthDataProvider(): \Generator
    {
        yield 'when person has 3 maxShiftsPerMonth' => [
            'maxShiftsPerMonth' => 3,
            'expectedResult' => false,
        ];
        yield 'when person has 2 maxShiftsPerMonth' => [
            'maxShiftsPerMonth' => 2,
            'expectedResult' => true,
        ];
        yield 'when person has 1 maxShiftsPerMonth' => [
            'maxShiftsPerMonth' => 1,
            'expectedResult' => true,
        ];
    }
}
