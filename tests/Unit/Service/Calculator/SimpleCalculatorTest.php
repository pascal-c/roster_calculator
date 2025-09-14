<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Entity\Roster;
use App\Entity\Shift;
use App\Service\Calculator\RosterCalculator\ShiftCalculator;
use App\Service\Calculator\SimpleCalculator;
use App\Service\ResultService;
use App\Value\Time\TimeSlotPeriod;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class SimpleCalculatorTest extends Unit
{
    private SimpleCalculator $simpleCalculator;
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

        $this->simpleCalculator = new SimpleCalculator(
            $this->resultService,
            $this->shiftCalculator,
        );
    }

    public function testCalculate(): void
    {
        $this->resultService
            ->expects($this->once())
            ->method('buildEmptyResult')
            ->with($this->roster)
            ->willReturn(['emptyResult']);
        $this->shiftCalculator
            ->expects($this->exactly(2))
            ->method('calculateSortedResultsForShift')
            ->willReturnMap([
                [['emptyResult'], $this->roster, $this->shift1, [['firstResult']]],
                [['firstResult'], $this->roster, $this->shift2, [['secondResult']]],
            ]);

        $result = $this->simpleCalculator->calculate($this->roster);

        $this->assertSame(['secondResult'], $result);
    }
}
