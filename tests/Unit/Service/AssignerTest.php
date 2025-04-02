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

        $this->fernando = Stub::make(Person::class, ['id' => 'fernando']);
        $this->thorsten = Stub::make(Person::class, ['id' => 'thorsten']);

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
        $firstResult = ['rating' => ['total' => 317]];
        $this->rater
            ->expects($this->exactly(4))
            ->method('calculatePoints')
            ->with($this->anything(), $this->roster)
            ->willReturnCallback(
                function (array $result, Roster $_roster): array {
                    $shiftAssignments = $this->resultService->getShiftAssignments($result);
                    if (2 === count($shiftAssignments) && $shiftAssignments['shift1']['addedPeople'] === [$this->fernando]) {
                        return ['total' => 317];
                    }

                    return ['total' => 316];
                }
            );

        $result = $this->assigner->calculateAll($this->roster, $this->shifts, $this->resultService->buildEmptyResult($this->roster), $firstResult);

        $expectedShiftAssignments = [ // Thorsten is not available for playDate 2, but thorsten is better for shift1
            'shift1' => ['shift' => $this->shift1, 'addedPeople' => [$this->thorsten]],
            'shift2' => ['shift' => $this->shift2, 'addedPeople' => [$this->fernando]],
        ];

        $this->assertEquals($expectedShiftAssignments, $this->resultService->getShiftAssignments($result));
        $this->assertSame(316, $this->resultService->getTotalPoints($result));
        $this->assertSame(2, $this->assigner->counter); // one for shift2, one for shift1
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
