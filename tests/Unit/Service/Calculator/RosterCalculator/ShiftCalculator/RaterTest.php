<?php

namespace Tests\Unit\Service\Calculator\RosterCalculator\ShiftCalculator;

use App\Entity\Availability;
use App\Entity\Person;
use App\Entity\RatingPointWeightings;
use App\Entity\Roster;
use App\Entity\Shift;
use App\Service\Calculator\RosterCalculator\ShiftCalculator\Rater;
use App\Service\ResultService;
use App\Value\Time\TimeSlotPeriod;
use Codeception\Attribute\DataProvider;
use Codeception\Stub;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

class RaterTest extends Unit
{
    private ResultService&MockObject $resultService;
    private Rater $rater;

    private Roster $roster;
    private Person&MockObject $person1;
    private Person&MockObject $person2;
    private Shift $shift1;
    private Shift $shift2;
    private Shift $shift3;

    protected function _before(): void
    {
        $this->resultService = $this->createMock(ResultService::class);
        $this->rater = new Rater($this->resultService);

        $this->roster = new Roster();
        $this->person1 = Stub::make(Person::class, ['id' => '1', 'targetShifts' => 1, 'maxShiftsPerWeek' => 1, 'getAvailabilityOn' => Availability::MAYBE]);
        $this->person2 = Stub::make(Person::class, ['id' => '2', 'targetShifts' => 3, 'maxShiftsPerWeek' => 1, 'getAvailabilityOn' => Availability::YES]);
        $this->roster->addPerson($this->person1);
        $this->roster->addPerson($this->person2);

        $this->shift1 = new Shift('shift1', new TimeSlotPeriod(new \DateTimeImmutable('2024-07-24'), TimeSlotPeriod::ALL), null, [$this->person2]);
        $this->shift2 = new Shift('shift2', new TimeSlotPeriod(new \DateTimeImmutable('2024-07-31'), TimeSlotPeriod::AM), null, []); // not assigned -> 100 points
        $this->shift3 = new Shift('shift3', new TimeSlotPeriod(new \DateTimeImmutable('2024-07-30'), TimeSlotPeriod::PM), null, [$this->person1]); // is only maybe available -> 1 point
        $this->roster->addShift($this->shift1);
        $this->roster->addShift($this->shift2);
        $this->roster->addShift($this->shift3);
        $this->roster->setRatingPointWeightings(new RatingPointWeightings());
    }

    #[DataProvider('dataProvider')]
    public function testCalculatePoints(int $resultShiftCount, int $expectedTargetPlayPoints): void
    {
        $result = ['the crazy' => 'result'];
        $shiftAssignments = [
            'shift1' => [
                'shift' => $this->shift1,
                'addedPeople' => [$this->person1], // is only maybe available -> 1 point
            ],
            'shift2' => [
                'shift' => $this->shift2,
                'addedPeople' => [$this->person2],
            ],
            'shift3' => [
                'shift' => $this->shift3,
                'addedPeople' => [], // not assigned -> 100 points
            ],
        ];

        $this->resultService->method('getShiftAssignments')->willReturn($shiftAssignments);
        $this->resultService->method('countShifts')->willReturn($resultShiftCount);
        $this->resultService->method('getCalculatedShifts')->willReturnMap([
            [$result, $this->person1, 1],
            [$result, $this->person2, 1], // targetShifts is 3 -> 4 points
        ]);
        $this->resultService->method('countShiftsPerWeek')->willReturnMap([
            [$result, $this->person1, '2024-31', 3], // maxPerWeek is 1 -> 20 points
            [$result, $this->person1, '2024-30', 1],
            [$result, $this->person2, '2024-31', 1],
            [$result, $this->person2, '2024-30', 0],
        ]);

        $points = $this->rater->calculatePoints($result, $this->roster);

        $expectedPoints = [
            'notAssigned' => 200,
            'maybeClown' => 2,
            'targetPlays' => $expectedTargetPlayPoints,
            'maxPerWeek' => 20,
            'total' => 222 + $expectedTargetPlayPoints,
        ];

        $this->assertSame($expectedPoints, $points);
    }

    public static function dataProvider(): \Generator
    {
        yield 'when all shifts are calculated' => [
            'resultShiftCount' => 3, // total shift count is 3
            'expectedTargetPlayPoints' => 4,
        ];

        yield 'when not all shifts are calculated yet' => [
            'resultShiftCount' => 2, // total shift count is 3
            'expectedTargetPlayPoints' => 0,
        ];
    }
}
