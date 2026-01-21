<?php

namespace Tests\Unit\Service\Calculator\RosterCalculator\ShiftCalculator;

use App\Entity\Availability;
use App\Entity\Location;
use App\Entity\LocationPreference;
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
        $location1 = new Location('location1');
        $this->person1 = Stub::make(Person::class, ['id' => '1', 'targetShifts' => 1, 'maxShiftsPerWeek' => 1, 'getAvailabilityOn' => Availability::MAYBE, 'locationPreferenceDefaultPoints' => 1]);
        $this->person2 = Stub::make(Person::class, ['id' => '2', 'targetShifts' => 3, 'maxShiftsPerWeek' => 1, 'getAvailabilityOn' => Availability::YES, 'locationPreferenceDefaultPoints' => 2]);
        $this->person1->addLocationPreference(new LocationPreference($location1, 10)); // 10 points for shift 1
        $this->person1->addLocationPreference(new LocationPreference(null, 5)); // 5 points for shift 3
        $this->person2->addLocationPreference(new LocationPreference($location1, 7)); // 7 points for shift 1

        $this->roster->addPerson($this->person1);
        $this->roster->addPerson($this->person2);

        $this->shift1 = new Shift('shift1', new TimeSlotPeriod(new \DateTimeImmutable('2024-07-24'), TimeSlotPeriod::ALL), $location1, [$this->person2], [$this->person2]);
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
                'addedPeople' => [$this->person1], // is only maybe available -> 1 point + person1 is not in team -> 3 points
            ],
            'shift2' => [
                'shift' => $this->shift2,
                'addedPeople' => [$this->person2], // default location preference -> 2 points
            ],
            'shift3' => [
                'shift' => $this->shift3,
                'addedPeople' => [], // not assigned -> 100 points
            ],
        ];

        $this->resultService->method('getShiftAssignments')->willReturn($shiftAssignments);
        $this->resultService->method('countShifts')->willReturn($resultShiftCount);
        $this->resultService->method('getCalculatedShifts')->willReturnMap([
            [$result, $this->person1, 3], // targetShifts is 1 -> 2 * 2 * 2 = 8 points (+ 2 * 2 = 4 points when shifts are missing)
            [$result, $this->person2, 1], // targetShifts is 3 -> 2 * 2 * 2 = 8 points
        ]);
        $this->resultService->method('countShiftsPerWeek')->willReturnMap([
            [$result, $this->person1, '2024-31', 3], // maxPerWeek is 1 -> 20 points
            [$result, $this->person1, '2024-30', 1],
            [$result, $this->person2, '2024-31', 1],
            [$result, $this->person2, '2024-30', 0],
        ]);

        $points = $this->rater->calculatePoints($result, $this->roster);

        $expectedPoints = [
            'missingPerson' => 200,
            'maybePerson' => 2,
            'targetShifts' => $expectedTargetPlayPoints,
            'maxPerWeek' => 20,
            'locationPreferences' => 24, // 10 + 7 (shift1) + 2 (shift2) + 5 (shift3)
            'personNotInTeam' => 3,
            'total' => 249 + $expectedTargetPlayPoints,
        ];

        $this->assertSame($expectedPoints, $points);
    }

    public static function dataProvider(): \Generator
    {
        yield 'when all shifts are calculated' => [
            'resultShiftCount' => 3, // total shift count is 3
            'expectedTargetPlayPoints' => 16, // 8 (person1) + 8 (person2)
        ];

        yield 'when not all shifts are calculated yet' => [
            'resultShiftCount' => 2, // total shift count is 3
            'expectedTargetPlayPoints' => 12, // 12 (person1) + 0 (person2)
        ];
    }
}
