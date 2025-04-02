<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Entity\Location;
use App\Entity\Person;
use App\Entity\Shift;
use App\Service\AvailabilityChecker;
use App\Service\MaxShiftsReachedChecker;
use App\Service\ResultService;
use App\Value\Gender;
use App\Value\Time\TimeSlotPeriod;
use Codeception\Attribute\DataProvider;
use Codeception\Stub;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class AvailabilityCheckerTest extends Unit
{
    private ResultService&MockObject $resultService;
    private MaxShiftsReachedChecker&MockObject $maxShiftsReachedChecker;
    private AvailabilityChecker $availabilityChecker;

    private array $result = ['anything'];

    public function _before(): void
    {
        $this->resultService = $this->createMock(ResultService::class);
        $this->maxShiftsReachedChecker = $this->createMock(MaxShiftsReachedChecker::class);
        $this->availabilityChecker = new AvailabilityChecker($this->maxShiftsReachedChecker, $this->resultService);
    }

    #[DataProvider('onlyMenDataProvider')]
    public function testOnlyMen(Gender $genderCheckedPerson, Gender $genderAssignedPerson, Gender $genderAddedPerson, bool $expectedResult): void
    {
        $person = $this->make(Person::class, ['gender' => $genderCheckedPerson]);
        $shift = $this->make(Shift::class, ['assignedPeople' => [
            $this->make(Person::class, ['gender' => Gender::FEMALE]),
            $this->make(Person::class, ['gender' => $genderAssignedPerson]),
        ]]);
        $this->resultService
            ->method('getAddedPeople')
            ->with($this->result, $shift)
            ->willReturn([
                $this->make(Person::class, ['gender' => Gender::FEMALE]),
                $this->make(Person::class, ['gender' => $genderAddedPerson]),
            ]);
        $this->maxShiftsReachedChecker->expects($this->never())->method($this->anything());
        $this->assertSame($expectedResult, $this->availabilityChecker->onlyMen($this->result, $shift, $person));
    }

    public function onlyMenDataProvider(): \Generator
    {
        yield 'when checkedPerson is not male' => [
            'genderCheckedPerson' => Gender::DIVERSE,
            'genderAssignedPerson' => Gender::MALE,
            'genderAddedPerson' => Gender::MALE,
            'expectedResult' => false,
        ];
        yield 'when checkedPerson is male but no other' => [
            'genderCheckedPerson' => Gender::MALE,
            'genderAssignedPerson' => Gender::FEMALE,
            'genderAddedPerson' => Gender::DIVERSE,
            'expectedResult' => false,
        ];
        yield 'when checkedPerson and an assigned person are male' => [
            'genderCheckedPerson' => Gender::MALE,
            'genderAssignedPerson' => Gender::MALE,
            'genderAddedPerson' => Gender::FEMALE,
            'expectedResult' => true,
        ];
        yield 'when checkedPerson and an added person are male' => [
            'genderCheckedPerson' => Gender::MALE,
            'genderAssignedPerson' => Gender::DIVERSE,
            'genderAddedPerson' => Gender::MALE,
            'expectedResult' => true,
        ];
    }

    #[DataProvider('isAlreadyAssignedWithinDataProvider')]
    public function testIsAlreadyAssignedWithin($timeSlot1Availability, $timeSlot2Availability, $expectedResult): void
    {
        $person = $this->make(Person::class);
        $timeSlotPeriod = new TimeSlotPeriod(new \DateTimeImmutable('2024-07-24'), TimeSlotPeriod::ALL);
        $this->resultService
            ->expects($this->atLeastOnce())
            ->method('isAssignedAtTimeSlot')
            ->willReturnMap([
                [$this->result, $person, $timeSlotPeriod->timeSlots[0], $timeSlot1Availability],
                [$this->result, $person, $timeSlotPeriod->timeSlots[1], $timeSlot2Availability],
            ]);
        $this->maxShiftsReachedChecker->expects($this->never())->method($this->anything());

        $this->assertSame($expectedResult, $this->availabilityChecker->isAlreadyAssignedWithin($this->result, $person, $timeSlotPeriod));
    }

    public function isAlreadyAssignedWithinDataProvider(): \Generator
    {
        yield 'when all timeSlots are false' => [
            'timeSlot1Availability' => false,
            'timeSlot2Availability' => false,
            'expectedResult' => false,
        ];
        yield 'when first timeSlots is true' => [
            'timeSlot1Availability' => true,
            'timeSlot2Availability' => false,
            'expectedResult' => true,
        ];
        yield 'when second timeSlots is true' => [
            'timeSlot1Availability' => false,
            'timeSlot2Availability' => true,
            'expectedResult' => true,
        ];
    }

    #[DataProvider('isBlockedDataProvider')]
    public function testIsBlocked(Person $person, ?Location $location, $expectedResult): void
    {
        $this->resultService->expects($this->never())->method($this->anything());
        $this->maxShiftsReachedChecker->expects($this->never())->method($this->anything());
        $this->assertSame($expectedResult, $this->availabilityChecker->isBlocked($location, $person));
    }

    public function isBlockedDataProvider(): \Generator
    {
        $person = $this->make(Person::class, ['id' => '1']);
        yield 'when location is null' => [
            'person' => $person,
            'location' => null,
            'expectedResult' => false,
        ];
        yield 'when person is not in location block list' => [
            'person' => $person,
            'location' => new Location('4711', [$this->make(Person::class)]),
            'expectedResult' => false,
        ];
        yield 'when person is in location block list' => [
            'person' => $person,
            'location' => new Location('4711', [$this->make(Person::class), $person]),
            'expectedResult' => true,
        ];
    }

    #[DataProvider('isAvailableForDataProvider')]
    public function testIsAvailableFor(bool $isAvailableOn, bool $isAlreadyAssignedWithin, bool $isBlocked, bool $maxShiftsPerMonthReached, bool $maxShiftsPerDayReached, bool $onlyMen, bool $expectedResult): void
    {
        $person = $this->make(Person::class, ['id' => '1', 'isAvailableOn' => $isAvailableOn]);
        $shift = $this->make(Shift::class, [
            'location' => new Location('1'),
            'timeSlotPeriod' => new TimeSlotPeriod(new \DateTimeImmutable('2024-07-24'), TimeSlotPeriod::ALL),
        ]);

        $availabilityChecker = Stub::construct(AvailabilityChecker::class,
            [
                'maxShiftsReachedChecker' => $this->maxShiftsReachedChecker,
                'resultService' => $this->resultService,
            ],
            [
                'isAlreadyAssignedWithin' => $isAlreadyAssignedWithin,
                'isBlocked' => $isBlocked,
                'onlyMen' => $onlyMen,
            ]
        );
        $availabilityChecker->method('isAlreadyAssignedWithin')->with($this->result, $person, $shift->timeSlotPeriod);
        $availabilityChecker->method('isBlocked')->with($shift->location, $person);
        $availabilityChecker->method('onlyMen')->with($this->result, $shift, $person);

        $this->maxShiftsReachedChecker
            ->method('maxShiftsPerMonthReached')
            ->with($person, $this->result)
            ->willReturn($maxShiftsPerMonthReached);
        $this->maxShiftsReachedChecker
            ->method('maxShiftsPerDayReached')
            ->with($shift->timeSlotPeriod->dateIndex, $person, $this->result)
            ->willReturn($maxShiftsPerDayReached);

        $this->assertSame($expectedResult, $availabilityChecker->isAvailableFor($shift, $person, $this->result));
    }

    public function isAvailableForDataProvider(): \Generator
    {
        yield 'when person is available' => [
            'isAvailableOn' => true,
            'isAlreadyAssignedWithin' => false,
            'isBlocked' => false,
            'maxShiftsPerMonthReached' => false,
            'maxShiftsPerDayReached' => false,
            'onlyMen' => false,
            'expectedResult' => true,
        ];
        yield 'when person is not available' => [
            'isAvailableOn' => false,
            'isAlreadyAssignedWithin' => false,
            'isBlocked' => false,
            'maxShiftsPerMonthReached' => false,
            'maxShiftsPerDayReached' => false,
            'onlyMen' => false,
            'expectedResult' => false,
        ];
        yield 'when person isAlreadyAssignedWithin' => [
            'isAvailableOn' => true,
            'isAlreadyAssignedWithin' => true,
            'isBlocked' => false,
            'maxShiftsPerMonthReached' => false,
            'maxShiftsPerDayReached' => false,
            'onlyMen' => false,
            'expectedResult' => false,
        ];
        yield 'when person isBlocked' => [
            'isAvailableOn' => true,
            'isAlreadyAssignedWithin' => false,
            'isBlocked' => true,
            'maxShiftsPerMonthReached' => false,
            'maxShiftsPerDayReached' => false,
            'onlyMen' => false,
            'expectedResult' => false,
        ];
        yield 'when person has maxShiftsPerMonthReached' => [
            'isAvailableOn' => true,
            'isAlreadyAssignedWithin' => false,
            'isBlocked' => false,
            'maxShiftsPerMonthReached' => true,
            'maxShiftsPerDayReached' => false,
            'onlyMen' => false,
            'expectedResult' => false,
        ];
        yield 'when person has maxShiftsPerDayReached' => [
            'isAvailableOn' => true,
            'isAlreadyAssignedWithin' => false,
            'isBlocked' => false,
            'maxShiftsPerMonthReached' => false,
            'maxShiftsPerDayReached' => true,
            'onlyMen' => false,
            'expectedResult' => false,
        ];
        yield 'when shift would have onlyMen' => [
            'isAvailableOn' => true,
            'isAlreadyAssignedWithin' => false,
            'isBlocked' => false,
            'maxShiftsPerMonthReached' => false,
            'maxShiftsPerDayReached' => false,
            'onlyMen' => true,
            'expectedResult' => false,
        ];
    }
}
