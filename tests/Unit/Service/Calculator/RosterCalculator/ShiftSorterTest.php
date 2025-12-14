<?php

declare(strict_types=1);

namespace Tests\Unit\Service\ice\Calculator\RosterCalculator;

use App\Entity\Person;
use App\Entity\Roster;
use App\Entity\Shift;
use App\Service\ArrayService;
use App\Service\Calculator\RosterCalculator\ShiftCalculator\AvailabilityChecker;
use App\Service\Calculator\RosterCalculator\ShiftCalculator\ResultSorter;
use App\Service\Calculator\RosterCalculator\ShiftSorter;
use App\Value\Time\TimeSlotPeriod;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class ShiftSorterTest extends Unit
{
    private ShiftSorter $shiftSorter;
    private AvailabilityChecker&MockObject $availabilityChecker;

    private Roster $roster;
    private Person&MockObject $person1;
    private Person&MockObject $person2;
    private Person&MockObject $person3;
    private Shift&MockObject $shift1;
    private Shift&MockObject $shift2;
    private Shift&MockObject $shift3;

    public function _before(): void
    {
        // data
        $timeSlotPeriod = new TimeSlotPeriod(new \DateTimeImmutable(), TimeSlotPeriod::AM);
        $this->shift1 = $this->make(Shift::class, ['id' => '1', 'stillNeededPeople' => 1, 'timeSlotPeriod' => $timeSlotPeriod]);
        $this->shift2 = $this->make(Shift::class, ['id' => '2', 'stillNeededPeople' => 1, 'timeSlotPeriod' => $timeSlotPeriod]);
        $this->shift3 = $this->make(Shift::class, ['id' => '3', 'stillNeededPeople' => 3, 'timeSlotPeriod' => $timeSlotPeriod]);
        $this->person1 = $this->make(Person::class, ['id' => 1]);
        $this->person2 = $this->make(Person::class, ['id' => 2]);
        $this->person3 = $this->make(Person::class, ['id' => 3]);

        $this->roster = new Roster();
        $this->roster
            ->addShift($this->shift1)
            ->addShift($this->shift2)
            ->addShift($this->shift3)
            ->addPerson($this->person1)
            ->addPerson($this->person2)
            ->addPerson($this->person3)
        ;

        // services
        $this->availabilityChecker = $this->createMock(AvailabilityChecker::class);
        $this->resultSorter = $this->createMock(ResultSorter::class);

        $this->shiftSorter = new ShiftSorter(
            $this->availabilityChecker,
            new ArrayService(),
        );
    }

    public function testSortByAvailabilities(): void
    {
        $result = ['result'];
        $this->availabilityChecker
            ->method('isAvailableFor')
            ->willReturnMap([
                [$this->shift1, $this->person1, $result, true],
                [$this->shift1, $this->person2, $result, true], // stillNeededPeople - availablePeople
                [$this->shift1, $this->person3, $result, false], // 2 -1 = 1

                [$this->shift2, $this->person1, $result, true],
                [$this->shift2, $this->person2, $result, true],
                [$this->shift2, $this->person3, $result, true], // 3 -1 = 2

                [$this->shift3, $this->person1, $result, true],
                [$this->shift3, $this->person2, $result, true],
                [$this->shift3, $this->person3, $result, true], // 3 - 3 = 0
            ]);

        $shifts = $this->roster->getShifts();
        $this->shiftSorter->sortByAvailabilities($result, $shifts, $this->roster);
        $this->assertEquals([$this->shift3, $this->shift1, $this->shift2], $shifts);
    }
}
