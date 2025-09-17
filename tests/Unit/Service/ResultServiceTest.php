<?php

namespace Tests\Unit\Entity;

use App\Entity\Location;
use App\Entity\Person;
use App\Entity\Roster;
use App\Entity\Shift;
use App\Service\ResultService;
use App\Value\Time\TimeSlot;
use App\Value\Time\TimeSlotPeriod;
use Codeception\Stub;

class ResultServiceTest extends \Codeception\Test\Unit
{
    private ResultService $resultService;
    private Roster $roster;
    private Person $person1;
    private Person $person2;

    public function _before(): void
    {
        $this->resultService = new ResultService();

        // data
        $this->roster = new Roster();
        $this->person1 = Stub::make(Person::class, ['id' => '1', 'targetShifts' => 5]);
        $this->person2 = Stub::make(Person::class, ['id' => '2']);
        $this->roster->addPerson($this->person1);
        $this->roster->addPerson($this->person2);
        $shift1 = new Shift('shift1', new TimeSlotPeriod(new \DateTimeImmutable('2024-07-24'), TimeSlotPeriod::ALL), new Location('1'), [$this->person1]);
        $shift2 = new Shift('shift2', new TimeSlotPeriod(new \DateTimeImmutable('2024-07-31'), TimeSlotPeriod::AM), null, [$this->person1, $this->person2]);
        $shift3 = new Shift('shift3', new TimeSlotPeriod(new \DateTimeImmutable('2024-07-30'), TimeSlotPeriod::PM), null, []);
        $this->roster->addShift($shift1);
        $this->roster->addShift($shift2);
        $this->roster->addShift($shift3);
    }

    public function testBuildEmptyResult(): void
    {
        $result = $this->resultService->buildEmptyResult($this->roster);
        $expectedResult = [
            'shifts' => [],
            'people' => [
                '1' => [
                    'person' => $this->person1,
                    'calculatedShifts' => 2,
                    'timeSlots' => [
                        '2024-07-24' => [
                            'all' => $this->roster->getShifts()[0]->timeSlotPeriod,
                        ],
                        '2024-07-31' => [
                            'am' => $this->roster->getShifts()[1]->timeSlotPeriod,
                        ],
                    ],
                ],
                '2' => [
                    'person' => $this->person2,
                    'calculatedShifts' => 1,
                    'timeSlots' => [
                        '2024-07-31' => [
                            'am' => $this->roster->getShifts()[1]->timeSlotPeriod,
                        ],
                    ],
                ],
            ],
        ];

        $this->assertSame($expectedResult, $result);
    }

    public function testAddEmpty(): void
    {
        $result = $this->resultService->buildEmptyResult($this->roster);
        $shift3 = $this->roster->getShifts()[2];
        $newResult = $this->resultService->add($result, $shift3, null);
        $expectedResult = [
            'shifts' => [
                'shift3' => [
                    'shift' => $shift3,
                    'addedPeople' => [],
                ],
            ],
            'people' => [
                '1' => [
                    'person' => $this->person1,
                    'calculatedShifts' => 2,
                    'timeSlots' => [
                        '2024-07-24' => [
                            'all' => $this->roster->getShifts()[0]->timeSlotPeriod,
                        ],
                        '2024-07-31' => [
                            'am' => $this->roster->getShifts()[1]->timeSlotPeriod,
                        ],
                    ],
                ],
                '2' => [
                    'person' => $this->person2,
                    'calculatedShifts' => 1,
                    'timeSlots' => [
                        '2024-07-31' => [
                            'am' => $this->roster->getShifts()[1]->timeSlotPeriod,
                        ],
                    ],
                ],
            ],
        ];

        $this->assertSame($expectedResult, $newResult);
    }

    public function testAddPerson(): void
    {
        $result = $this->resultService->buildEmptyResult($this->roster);
        $shift3 = $this->roster->getShifts()[2];
        $newResult = $this->resultService->add($result, $shift3, $this->person1);
        $expectedResult = [
            'shifts' => [
                'shift3' => [
                    'shift' => $shift3,
                    'addedPeople' => [$this->person1],
                ]],
            'people' => [
                '1' => [
                    'person' => $this->person1,
                    'calculatedShifts' => 3,
                    'timeSlots' => [
                        '2024-07-24' => [
                            'all' => $this->roster->getShifts()[0]->timeSlotPeriod,
                        ],
                        '2024-07-31' => [
                            'am' => $this->roster->getShifts()[1]->timeSlotPeriod,
                        ],
                        '2024-07-30' => [
                            'pm' => $this->roster->getShifts()[2]->timeSlotPeriod,
                        ],
                    ],
                ],
                '2' => [
                    'person' => $this->person2,
                    'calculatedShifts' => 1,
                    'timeSlots' => [
                        '2024-07-31' => [
                            'am' => $this->roster->getShifts()[1]->timeSlotPeriod,
                        ],
                    ],
                ],
            ],
        ];
        $this->assertSame($expectedResult, $newResult);

        $newResult = $this->resultService->add($newResult, $shift3, $this->person2);
        $expectedResult = [
            'shifts' => [
                'shift3' => [
                    'shift' => $shift3,
                    'addedPeople' => [$this->person1, $this->person2],
                ],
            ],
            'people' => [
                '1' => [
                    'person' => $this->person1,
                    'calculatedShifts' => 3,
                    'timeSlots' => [
                        '2024-07-24' => [
                            'all' => $this->roster->getShifts()[0]->timeSlotPeriod,
                        ],
                        '2024-07-31' => [
                            'am' => $this->roster->getShifts()[1]->timeSlotPeriod,
                        ],
                        '2024-07-30' => [
                            'pm' => $this->roster->getShifts()[2]->timeSlotPeriod,
                        ],
                    ],
                ],
                '2' => [
                    'person' => $this->person2,
                    'calculatedShifts' => 2,
                    'timeSlots' => [
                        '2024-07-31' => [
                            'am' => $this->roster->getShifts()[1]->timeSlotPeriod,
                        ],
                        '2024-07-30' => [
                            'pm' => $this->roster->getShifts()[2]->timeSlotPeriod,
                        ],
                    ],
                ],
            ],
        ];
        $this->assertSame($expectedResult, $newResult);
    }

    public function testSetAndGetRating(): void
    {
        $result = $this->resultService->buildEmptyResult($this->roster);
        $this->assertSame([], $this->resultService->getRating($result));

        $this->resultService->setRating($result, ['total' => 42]);
        $this->assertSame(['total' => 42], $this->resultService->getRating($result));
        $this->assertSame(42, $this->resultService->getTotalPoints($result));
    }

    public function testGetShiftAssignments(): void
    {
        $shift3 = $this->roster->getShifts()[2];
        $result1 = $this->resultService->buildEmptyResult($this->roster);
        $result2 = $this->resultService->add($result1, $shift3, $this->person1);
        $expectedShiftAssignments = [
            'shift3' => ['shift' => $shift3, 'addedPeople' => [$this->person1]],
        ];
        $this->assertSame($expectedShiftAssignments, $this->resultService->getShiftAssignments($result2));
    }

    public function testGetShifts(): void
    {
        $shift3 = $this->roster->getShifts()[2];
        $result1 = $this->resultService->buildEmptyResult($this->roster);
        $result2 = $this->resultService->add($result1, $shift3, $this->person1);
        $this->assertSame([$shift3], $this->resultService->getShifts($result2));
    }

    public function testGetAddedPeople(): void
    {
        $shift3 = $this->roster->getShifts()[2];
        $result1 = $this->resultService->buildEmptyResult($this->roster);
        $result2 = $this->resultService->add($result1, $shift3, $this->person1);
        $result3 = $this->resultService->add($result2, $shift3, $this->person2);

        $addedPeople1 = $this->resultService->getAddedPeople($result1, $shift3);
        $addedPeople2 = $this->resultService->getAddedPeople($result2, $shift3);
        $addedPeople3 = $this->resultService->getAddedPeople($result3, $shift3);
        $this->assertEquals([], $addedPeople1);
        $this->assertEquals([$this->person1], $addedPeople2);
        $this->assertEquals([$this->person1, $this->person2], $addedPeople3);
    }

    public function testGetLastAddedPerson(): void
    {
        $shift3 = $this->roster->getShifts()[2];
        $result1 = $this->resultService->buildEmptyResult($this->roster);
        $result2 = $this->resultService->add($result1, $shift3, $this->person1);
        $result3 = $this->resultService->add($result2, $shift3, $this->person2);

        $this->assertNull($this->resultService->getLastAddedPerson($result1, $shift3));
        $this->assertSame($this->person1, $this->resultService->getLastAddedPerson($result2, $shift3));
        $this->assertSame($this->person2, $this->resultService->getLastAddedPerson($result3, $shift3));
    }

    public function testCountShiftsPerDay(): void
    {
        $result = $this->resultService->buildEmptyResult($this->roster);
        $count = $this->resultService->countShiftsPerDay($result, $this->person1, '2024-07-24');
        $this->assertSame(1, $count);

        $count = $this->resultService->countShiftsPerDay($result, $this->person1, '2024-07-23');
        $this->assertSame(0, $count);

        $count = $this->resultService->countShiftsPerDay($result, $this->person1, '2024-07-31');
        $this->assertSame(1, $count);
    }

    public function testCountShiftsPerWeek(): void
    {
        $result = $this->resultService->buildEmptyResult($this->roster);
        $count = $this->resultService->countShiftsPerWeek($result, $this->person1, '2024-31'); // weekId for 2024-07-30 and 2024-07-31
        $this->assertSame(1, $count);
        $count = $this->resultService->countShiftsPerWeek($result, $this->person1, '2024-30'); // weekId for 2024-07-24
        $this->assertSame(1, $count);

        $newResult = $this->resultService->add($result, $this->roster->getShifts()[2], $this->person1);
        $count = $this->resultService->countShiftsPerWeek($newResult, $this->person1, '2024-31');
        $this->assertSame(2, $count);
    }

    public function testGetCalculatedShifts(): void
    {
        $result = $this->resultService->buildEmptyResult($this->roster);
        $calculatedShifts = $this->resultService->getCalculatedShifts($result, $this->person1);
        $this->assertSame(2, $calculatedShifts);
    }

    public function testGetAllCalculatedShifts(): void
    {
        $result = $this->resultService->buildEmptyResult($this->roster);
        $expectedResult = [
            ['personId' => '1', 'calculatedShifts' => 2],
            ['personId' => '2', 'calculatedShifts' => 1],
        ];
        $this->assertSame($expectedResult, $this->resultService->getAllCalculatedShifts($result));
    }

    public function testGetOpenTargetShifts(): void
    {
        $result = $this->resultService->buildEmptyResult($this->roster);
        $openTargetShifts = $this->resultService->getOpenTargetShifts($result, $this->person1);
        $this->assertSame(3, $openTargetShifts); // 5 - 2 = 3
    }

    public function testIsAssignedAtTimeSlot(): void
    {
        $result = $this->resultService->buildEmptyResult($this->roster);

        $timeSlot = new TimeSlot(new \DateTimeImmutable('2024-07-24'), TimeSlotPeriod::AM);
        $isAssigned = $this->resultService->isAssignedAtTimeSlot($result, $this->person1, $timeSlot);
        $this->assertTrue($isAssigned);

        $timeSlot = new TimeSlot(new \DateTimeImmutable('2024-07-24'), TimeSlotPeriod::PM);
        $isAssigned = $this->resultService->isAssignedAtTimeSlot($result, $this->person1, $timeSlot);
        $this->assertTrue($isAssigned);

        $timeSlot = new TimeSlot(new \DateTimeImmutable('2024-07-23'), TimeSlotPeriod::AM);
        $isAssigned = $this->resultService->isAssignedAtTimeSlot($result, $this->person1, $timeSlot);
        $this->assertFalse($isAssigned);
    }
}
