<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Entity\Person;
use App\Entity\Roster;
use App\Entity\Shift;
use App\Service\AvailabilityChecker;
use App\Service\Calculator\RosterCalculator\ShiftCalculator;
use App\Service\Rater;
use App\Service\ResultService;
use App\Service\ResultSorter;
use App\Value\Time\TimeSlotPeriod;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class ShiftCalculaotorTest extends Unit
{
    private ShiftCalculator $shiftCalculator;
    private ResultService&MockObject $resultService;
    private AvailabilityChecker&MockObject $availabilityChecker;
    private ResultSorter&MockObject $resultSorter;
    private Rater&MockObject $rater;

    private Roster $roster;
    private Shift $shift;

    public function _before(): void
    {
        // data
        $this->shift = $this->make(Shift::class, [
            'stillNeededPeople' => 2,
            'timeSlotPeriod' => new TimeSlotPeriod(new \DateTimeImmutable(), TimeSlotPeriod::AM)]
        );
        $this->person1 = $this->make(Person::class, ['id' => 1]);
        $this->person2 = $this->make(Person::class, ['id' => 2]);
        $this->person3 = $this->make(Person::class, ['id' => 3]);
        $this->person4 = $this->make(Person::class, ['id' => 4]);

        $this->roster = new Roster();
        $this->roster
            ->addShift($this->shift)
            ->addPerson($this->person1)
            ->addPerson($this->person2)
            ->addPerson($this->person3)
            ->addPerson($this->person4)
        ;

        // services
        $this->resultService = $this->createMock(ResultService::class);
        $this->availabilityChecker = $this->createMock(AvailabilityChecker::class);
        $this->resultSorter = $this->createMock(ResultSorter::class);
        $this->rater = $this->createMock(Rater::class);

        $this->shiftCalculator = new ShiftCalculator(
            $this->resultService,
            $this->availabilityChecker,
            $this->resultSorter,
            $this->rater,
        );
    }

    public function testCalculate(): void
    {
        $expectedResults = [
            ['result nobody'],
            ['result person1'],
            ['result person2'],
            ['result person4'],
            ['result person1 person2'],
            ['result person2 person4'],
        ];

        $this->availabilityChecker
            ->expects($this->exactly(10))
            ->method('isAvailableFor')
            ->willReturnMap([
                [$this->shift, $this->person1, ['startResult'], true],
                [$this->shift, $this->person2, ['startResult'], true],
                [$this->shift, $this->person3, ['startResult'], false],
                [$this->shift, $this->person4, ['startResult'], true],

                [$this->shift, $this->person1, ['result nobody'], true],
                [$this->shift, $this->person2, ['result nobody'], true],
                [$this->shift, $this->person4, ['result nobody'], true],

                [$this->shift, $this->person2, ['result person1'], true],
                [$this->shift, $this->person4, ['result person1'], false],
                [$this->shift, $this->person4, ['result person2'], true],
            ]);
        $this->resultService
            ->expects($this->exactly(6))
            ->method('add')
            ->willReturnMap([
                [['startResult'], $this->shift, null, ['result nobody']],
                [['result nobody'], $this->shift, $this->person1, ['result person1']],
                [['result nobody'], $this->shift, $this->person2, ['result person2']],
                [['result nobody'], $this->shift, $this->person4, ['result person4']],
                [['result person1'], $this->shift, $this->person2, ['result person1 person2']],
                [['result person2'], $this->shift, $this->person4, ['result person2 person4']],
            ]);
        $this->resultService
            ->method('getLastAddedPerson')
            ->with($this->anything(), $this->shift)
            ->willReturnMap([
                [['startResult'], $this->shift, null],
                [['result nobody'], $this->shift, null],
                [['result person1'], $this->shift, $this->person1],
                [['result person2'], $this->shift, $this->person2],
                [['result person4'], $this->shift, $this->person4],
            ]);
        $this->resultSorter
            ->expects($this->once())
            ->method('sortForShift')
            ->with($expectedResults, $this->shift);

        $results = $this->shiftCalculator->calculateSortedResultsForShift(['startResult'], $this->roster, $this->shift);
        $this->assertSame($expectedResults, $results);
    }
}
