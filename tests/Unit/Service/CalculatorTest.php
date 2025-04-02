<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Entity\Roster;
use App\Entity\Shift;
use App\Service\Assigner;
use App\Service\Calculator;
use App\Service\ResultService;
use App\Value\Time\TimeSlotPeriod;
use Codeception\Stub;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class CalculatorTest extends Unit
{
    private Calculator $calculator;
    private Assigner&MockObject $assigner;
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
        $this->assigner = $this->createMock(Assigner::class);
        $this->resultService = $this->createMock(ResultService::class);

        $this->calculator = new Calculator(
            $this->assigner,
            $this->resultService,
        );

        $this->resultService
            ->method('buildEmptyResult')
            ->with($this->roster)
            ->willReturn($this->emptyResult);
        $this->assigner
            ->method('calculateFirst')
            ->with($this->roster)
            ->willReturn($this->firstResult);
        $this->assigner
            ->method('calculateAll')
            ->with($this->roster, [$shift2, $shift1], $this->emptyResult, $this->firstResult)
            ->willReturn($this->bestResult);
    }

    public function testCalculate(): void
    {
        $result = $this->calculator->calculate($this->roster);
        $this->assertSame($this->bestResult, $result);
    }
}
