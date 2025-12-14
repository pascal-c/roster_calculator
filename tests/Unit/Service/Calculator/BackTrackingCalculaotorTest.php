<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Calculator;

use App\Entity\Roster;
use App\Entity\Shift;
use App\Service\Calculator\BackTrackingCalculator;
use App\Service\Calculator\RosterCalculator\ShiftCalculator;
use App\Service\Calculator\RosterCalculator\ShiftSorter;
use App\Service\ResultService;
use App\Service\TimeService;
use App\Value\Time\TimeSlotPeriod;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class BackTrackingCalculaotorTest extends Unit
{
    private BackTrackingCalculator $backTrackingCalculator;
    private ResultService&MockObject $resultService;
    private ShiftCalculator&MockObject $shiftCalculator;
    private TimeService&MockObject $timeService;
    private ShiftSorter&MockObject $shiftSorter;

    private Roster $roster;
    private Shift $shift1;
    private Shift $shift2;

    public function _before(): void
    {
        // data
        $this->shift1 = $this->make(Shift::class, ['timeSlotPeriod' => new TimeSlotPeriod(new \DateTimeImmutable(), TimeSlotPeriod::AM)]);
        $this->shift2 = $this->make(Shift::class, ['timeSlotPeriod' => new TimeSlotPeriod(new \DateTimeImmutable(), TimeSlotPeriod::PM)]);

        $this->roster = new Roster();
        $this->roster
            ->addShift($this->shift1)
            ->addShift($this->shift2);

        // services
        $this->resultService = $this->createMock(ResultService::class);
        $this->shiftCalculator = $this->createMock(ShiftCalculator::class);
        $this->timeService = $this->createMock(TimeService::class);
        $this->shiftSorter = $this->createMock(ShiftSorter::class);
    }

    public function testCalculate(): void
    {
        $this->timeService
            ->method('unixTimestamp')
            ->willReturnOnConsecutiveCalls(100, 185, 185, 185, 185); // first call is start time, then no timeout
        $this->backTrackingCalculator = new BackTrackingCalculator(
            $this->resultService,
            $this->shiftCalculator,
            $this->timeService,
            $this->shiftSorter,
        );
        $this->resultService
            ->expects($this->exactly(8))
            ->method('getTotalPoints')
            ->willReturnMap([
                [['bestResult'], 22],
                [['result1'], 10],
                [['result2'], 11],
                [['result1.1'], 15],
                [['result2.1'], 16],
            ]);
        $this->shiftCalculator
            ->expects($this->exactly(3))
            ->method('calculateSortedResultsForShift')
            ->willReturnMap([
                [['currentResult'], $this->roster, $this->shift1, [['result1'], ['result2']]],
                [['result1'], $this->roster, $this->shift2, [['result1.1'], ['result1.2']]],
                [['result2'], $this->roster, $this->shift2, [['result2.1'], ['result2.2']]],
            ]);
        $this->shiftSorter->expects($this->atLeastOnce())->method('sortByAvailabilities');

        $result = $this->backTrackingCalculator->calculate($this->roster, [$this->shift1, $this->shift2], ['currentResult'], ['bestResult']);

        $this->assertSame(['result1.1'], $result);
        $this->assertFalse($this->backTrackingCalculator->isTimedOut());
        $this->assertSame(3, $this->backTrackingCalculator->counter);
    }

    public function testCalculateWithTimeout(): void
    {
        $this->timeService
            ->method('unixTimestamp')
            ->willReturnOnConsecutiveCalls(100, 186, 186); // first call is start time this will trigger the timeout
        $this->backTrackingCalculator = new BackTrackingCalculator(
            $this->resultService,
            $this->shiftCalculator,
            $this->timeService,
            $this->shiftSorter,
        );
        $this->resultService
            ->expects($this->never())
            ->method($this->anything());
        $this->shiftCalculator
            ->expects($this->never())
            ->method($this->anything());
        $this->shiftSorter
            ->expects($this->never())
            ->method($this->anything());

        $result = $this->backTrackingCalculator->calculate($this->roster, [$this->shift2, $this->shift1], ['currentResult'], ['bestResult']);

        $this->assertSame(['bestResult'], $result);
        $this->assertTrue($this->backTrackingCalculator->isTimedOut());
        $this->assertSame(1, $this->backTrackingCalculator->counter);
    }
}
