<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Entity\Roster;
use App\Entity\Shift;
use App\Service\Calculator;
use App\Service\Calculator\BackTrackingCalculator;
use App\Service\Calculator\SimpleCalculator;
use App\Service\ResultService;
use App\Value\Time\TimeSlotPeriod;
use Codeception\Stub;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class CalculatorTest extends Unit
{
    private Calculator $calculator;
    private BackTrackingCalculator&MockObject $backTrackingCalculator;
    private SimpleCalculator&MockObject $simpleCalculator;
    private ResultService&MockObject $resultService;
    private array $firstResult = ['firstResult'];
    private array $bestResult = ['bestResult'];
    private array $emptyResult = ['emptyResult'];

    public function _before(): void
    {
        // data
        $timeSlotPeriod = new TimeSlotPeriod(new \DateTimeImmutable(), TimeSlotPeriod::PM);
        $this->roster = new Roster();
        $this->roster->addShift($shift1 = Stub::make(Shift::class, ['id' => 'shift1', 'timeSlotPeriod' => $timeSlotPeriod]));
        $this->roster->addShift($shift2 = Stub::make(Shift::class, ['id' => 'shift2', 'timeSlotPeriod' => $timeSlotPeriod]));

        // services
        $this->simpleCalculator = $this->createMock(SimpleCalculator::class);
        $this->backTrackingCalculator = $this->createMock(BackTrackingCalculator::class);
        $this->resultService = $this->createMock(ResultService::class);

        $this->calculator = new Calculator(
            $this->simpleCalculator,
            $this->backTrackingCalculator,
            $this->resultService,
        );

        $this->resultService
            ->method('buildEmptyResult')
            ->with($this->roster)
            ->willReturn($this->emptyResult);
        $this->simpleCalculator
            ->method('calculate')
            ->with($this->roster)
            ->willReturn($this->firstResult);
        $this->backTrackingCalculator
            ->method('calculate')
            ->with($this->roster, [$shift1, $shift2], $this->emptyResult, $this->firstResult)
            ->willReturn($this->bestResult);
    }

    public function testCalculate(): void
    {
        $result = $this->calculator->calculate($this->roster);
        $this->assertSame($this->bestResult, $result);
    }
}
