<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Entity\Person;
use App\Entity\Roster;
use App\Entity\Shift;
use App\Service\Assigner;
use App\Service\AvailabilityChecker;
use App\Service\PeopleSorter;
use App\Service\Rater;
use App\Service\ResultService;
use App\Value\Time\TimeSlotPeriod;
use Codeception\Stub;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class AssignerTest extends Unit
{
    private Assigner $assigner;
    private AvailabilityChecker|MockObject $availabilityChecker;
    private PeopleSorter|MockObject $peopleSorter;
    private Rater|MockObject $rater;
    private ResultService $resultService;

    private Roster $roster;
    private array $shifts;
    private Shift $shift1;
    private Shift $shift2;
    private array $people;
    private Person $fernando;
    private Person $thorsten;

    public function _before(): void
    {
        // data
        $timeSlotPeriod = new TimeSlotPeriod(new \DateTimeImmutable(), TimeSlotPeriod::PM);
        $this->shifts = [
            $this->shift1 = Stub::make(Shift::class, ['id' => 'shift1', 'timeSlotPeriod' => $timeSlotPeriod, 'assignedPeople' => []]),
            $this->shift2 = Stub::make(Shift::class, ['id' => 'shift2', 'timeSlotPeriod' => $timeSlotPeriod, 'assignedPeople' => []]),
        ];
        $this->people = [
            $this->fernando = Stub::make(Person::class, ['id' => 'fernando']),
            $this->thorsten = Stub::make(Person::class, ['id' => 'thorsten']),
        ];
        $this->roster = new Roster();
        $this->roster
            ->addPerson($this->fernando)
            ->addPerson($this->thorsten)
            ->addShift($this->shift1)
            ->addShift($this->shift2);

        // services
        $this->availabilityChecker = $this->createMock(AvailabilityChecker::class);
        $this->peopleSorter = $this->createMock(PeopleSorter::class);
        $this->rater = $this->createMock(Rater::class);
        $this->resultService = new ResultService();

        $this->assigner = new Assigner(
            $this->resultService,
            $this->availabilityChecker,
            $this->peopleSorter,
            $this->rater,
        );

        $this->availabilityChecker->method('isAvailableFor')->willReturnCallback( // Thorsten is not available for playDate 2
            fn (Shift $shift, Person $person, array $_result) => $shift != $this->shift2 || $person != $this->thorsten
        );
        $this->peopleSorter->method('sortForShift')->willReturnCallback(
            fn (Shift $_shift, array $people, array $_result) => array_values($people)
        );
    }

    public function testCalculateAll(): void
    {
        $firstResultRating = 317;
        $this->rater
            ->expects($this->exactly(7)) // 3 times for first shift and 2x2 times for second shift
            ->method('calculatePoints')
            ->with($this->anything(), $this->roster)
            ->willReturnCallback(
                function (array $result, Roster $roster): array {
                    static $count = 0;
                    ++$count;

                    return (3 === $count) ? ['total' => 317] : ['total' => 316]; // null for first play date is definetely worse, everything else not
                }
            );

        $results = $this->assigner->calculateAll($this->roster, $this->shifts, $firstResultRating);
        $this->assertSame(4, count($results));

        $expectedShiftAssignments = [ // Thorsten is not available for playDate 2
            [
                'shift1' => ['shift' => $this->shift1, 'addedPeople' => [$this->fernando]],
                'shift2' => ['shift' => $this->shift2, 'addedPeople' => [$this->fernando]],
            ],
            [
                'shift1' => ['shift' => $this->shift1, 'addedPeople' => [$this->fernando]],
                'shift2' => ['shift' => $this->shift2, 'addedPeople' => []],
            ],
            [
                'shift1' => ['shift' => $this->shift1, 'addedPeople' => [$this->thorsten]],
                'shift2' => ['shift' => $this->shift2, 'addedPeople' => [$this->fernando]],
            ],
            [
                'shift1' => ['shift' => $this->shift1, 'addedPeople' => [$this->thorsten]],
                'shift2' => ['shift' => $this->shift2, 'addedPeople' => []],
            ],
        ];

        $this->assertEquals($expectedShiftAssignments, array_map(fn (array $result): array => $result['shifts'], $results));
        foreach ($results as $result) {
            $this->assertSame(316, $result['rating']['total']);
        }
    }

    public function testCalculateFirst(): void
    {
        $this->rater
            ->expects($this->once())
            ->method('calculatePoints')
            ->with($this->anything(), $this->roster)
            ->willReturn(['total' => 22]);

        $result = $this->assigner->calculateFirst($this->roster);

        $expectedResult = [ // Thorsten is not available for playDate 2
            'shift1' => ['shift' => $this->shift1, 'addedPeople' => [$this->fernando]],
            'shift2' => ['shift' => $this->shift2, 'addedPeople' => [$this->fernando]],
        ];

        $this->assertSame($expectedResult, $result['shifts']);
        $this->assertSame(22, $result['rating']['total']);
    }
}
