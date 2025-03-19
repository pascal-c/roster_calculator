<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Entity\Roster;
use App\Entity\Shift;
use App\Service\Assigner;
use App\Service\Calculator;
use App\Service\ResultService;
use App\Value\Time\TimeSlotPeriod;
use Codeception\Attribute\DataProvider;
use Codeception\Stub;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class CalculatorTest extends Unit
{
    private Calculator $calculator;
    private Assigner&MockObject $assigner;
    private ResultService&MockObject $resultService;
    private array $firstResult = ['firstResult'];
    private array $secondResult = ['secondResult'];
    private array $thirdResult = ['thirdResult'];

    public function _before(): void
    {
        // data
        $timeSlotPeriod = new TimeSlotPeriod(new \DateTimeImmutable(), TimeSlotPeriod::PM);
        $this->roster = new Roster();
        $this->roster->addShift(Stub::make(Shift::class, ['id' => 'shift1', 'timeSlotPeriod' => $timeSlotPeriod]));

        // services
        $this->assigner = $this->createMock(Assigner::class);
        $this->resultService = $this->createMock(ResultService::class);

        $this->calculator = new Calculator(
            $this->assigner,
            $this->resultService,
        );

        $this->assigner
            ->method('calculateFirst')
            ->with($this->roster)
            ->willReturn($this->firstResult);
        $this->assigner
            ->method('calculateAll')
            ->with($this->roster)
            ->willReturn([$this->secondResult, $this->thirdResult]);
    }

    #[DataProvider('dataProvider')]
    public function testCalculate(int $firstResultTotalPoints, int $secondResultTotalPoints, int $thirdResultTotalPoints, array $expectedResult): void
    {
        $this->resultService
            ->method('getTotalPoints')
            ->willReturnMap([
                [$this->firstResult, $firstResultTotalPoints],
                [$this->secondResult, $secondResultTotalPoints],
                [$this->thirdResult, $thirdResultTotalPoints],
            ]);

        $result = $this->calculator->calculate($this->roster);
        $this->assertSame($expectedResult, $result);
    }

    public static function dataProvider(): \Generator
    {
        yield 'when third result is best' => [
            'firstResultTotalPoints' => 23,
            'secondResultTotalPoints' => 24,
            'thirdResultTotalPoints' => 22,
            'expectedResult' => ['thirdResult'],
        ];

        yield 'when first result is best' => [
            'firstResultTotalPoints' => 22,
            'secondResultTotalPoints' => 22,
            'thirdResultTotalPoints' => 22,
            'expectedResult' => ['firstResult'],
        ];

        yield 'when second result is best' => [
            'firstResultTotalPoints' => 23,
            'secondResultTotalPoints' => 22,
            'thirdResultTotalPoints' => 22,
            'expectedResult' => ['secondResult'],
        ];
    }
}
