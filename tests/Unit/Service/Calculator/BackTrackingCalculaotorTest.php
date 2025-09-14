<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Entity\Roster;
use App\Entity\Shift;
use App\Service\Calculator\BackTrackingCalculator;
use App\Service\Calculator\RosterCalculator\ShiftCalculator;
use App\Service\ResultService;
use App\Value\Time\TimeSlotPeriod;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class BackTrackingCalculaotorTest extends Unit
{
    private BackTrackingCalculator $backTrackingCalculator;
    private ResultService&MockObject $resultService;
    private ShiftCalculator&MockObject $shiftCalculator;

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

        $this->backTrackingCalculator = new BackTrackingCalculator(
            $this->resultService,
            $this->shiftCalculator,
        );
    }

    public function testCalculate(): void
    {
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

        $result = $this->backTrackingCalculator->calculate($this->roster, [$this->shift2, $this->shift1], ['currentResult'], ['bestResult']);

        $this->assertSame(['result1.1'], $result);
    }
}
