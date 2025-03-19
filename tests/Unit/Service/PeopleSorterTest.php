<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Entity\Person;
use App\Entity\Shift;
use App\Service\MaxShiftsReachedChecker;
use App\Service\PeopleSorter;
use App\Service\ResultService;
use App\Value\Time\TimeSlotPeriod;
use Codeception\Attribute\DataProvider;
use Codeception\Stub;
use Codeception\Stub\Expected;
use Codeception\Test\Unit;

final class PeopleSorterTest extends Unit
{
    private const PERSON1_FIRST = 'person 1 comes first';
    private const PERSON2_FIRST = 'person 2 comes first';

    #[DataProvider('dataProvider')]
    public function testSortForShift(
        bool $maxShiftsWeekReached1,
        bool $maxShiftsWeekReached2,
        string $availability1,
        string $availability2,
        int $openTargetShifts1,
        int $openTargetShifts2,
        string $expectedFirstPerson,
    ): void {
        $date = new \DateTimeImmutable('2017-01-01');
        $shift = new Shift('1', new TimeSlotPeriod($date, TimeSlotPeriod::AM), null, []);
        $person1 = Stub::make(Person::class, [
            'id' => 'p1',
            'getAvailabilityOn' => $availability1,
        ]);
        $person2 = Stub::make(Person::class, [
            'id' => 'p2',
            'getAvailabilityOn' => $availability2,
        ]);
        $people = [$person1, $person2];
        $result = ['huhu' => 'haha'];

        $maxShiftsReachedChecker = Stub::make(MaxShiftsReachedChecker::class, [
            'maxShiftsPerWeekReached' => Expected::exactly(2,
                function (string $checkedWeek, Person $checkedPerson) use ($person1, $person2, $maxShiftsWeekReached1, $maxShiftsWeekReached2): bool {
                    $this->assertEquals('2016-52', $checkedWeek);
                    $this->assertContainsEquals($checkedPerson, [$person1, $person2]);

                    return ($person1 == $checkedPerson) ? $maxShiftsWeekReached1 : $maxShiftsWeekReached2;
                }
            ),
        ]);
        $resultService = Stub::make(ResultService::class, [
            'getOpenTargetShifts' => Expected::exactly(2,
                function (array $checkedResult, Person $checkedPerson) use ($person1, $person2, $openTargetShifts1, $openTargetShifts2): int {
                    $this->assertContainsEquals($checkedPerson, [$person1, $person2]);
                    $this->assertEquals(['huhu' => 'haha'], $checkedResult);

                    return ($person1 == $checkedPerson) ? $openTargetShifts1 : $openTargetShifts2;
                }
            ),
        ]);
        $sorter = new PeopleSorter(maxShiftsReachedChecker: $maxShiftsReachedChecker, resultService: $resultService);

        $result = $sorter->sortForShift($shift, $people, $result);
        if (self::PERSON1_FIRST === $expectedFirstPerson) {
            $this->assertEquals([$person1, $person2], $result);
        } else {
            $this->assertEquals([$person2, $person1], $result);
        }
    }

    public static function dataProvider(): \Generator
    {
        yield 'when max shifts week reached for person1' => [
            'maxShiftsWeekReached1' => true,
            'maxShiftsWeekReached2' => false,
            'availability1' => 'yes',
            'availability2' => 'maybe',
            'openTargetShifts1' => 10,
            'openTargetShifts2' => 1,

            'expectedFirstPerson' => self::PERSON2_FIRST,
        ];

        yield 'when max shifts week reached for both' => [
            'maxShiftsWeekReached1' => true,
            'maxShiftsWeekReached2' => true,
            'availability1' => 'yes',
            'availability2' => 'maybe',
            'openTargetShifts1' => 10,
            'openTargetShifts2' => 1,

            'expectedFirstPerson' => self::PERSON1_FIRST,
        ];

        yield 'when max shifts week reached for nobody and availability is yes for person2 and maybe for person1' => [
            'maxShiftsWeekReached1' => true,
            'maxShiftsWeekReached2' => true,
            'availability1' => 'maybe',
            'availability2' => 'yes',
            'openTargetShifts1' => 10,
            'openTargetShifts2' => 1,

            'expectedFirstPerson' => self::PERSON2_FIRST,
        ];

        yield 'when max shifts week reached for nobody and availability is yes for person1 and maybe for person2' => [
            'maxShiftsWeekReached1' => true,
            'maxShiftsWeekReached2' => true,
            'availability1' => 'yes',
            'availability2' => 'maybe',
            'openTargetShifts1' => 1,
            'openTargetShifts2' => 10,

            'expectedFirstPerson' => self::PERSON1_FIRST,
        ];

        yield 'when max shifts week reached for nobody and availability is same and person1 has more open shifts' => [
            'maxShiftsWeekReached1' => true,
            'maxShiftsWeekReached2' => true,
            'availability1' => 'yes',
            'availability2' => 'yes',
            'openTargetShifts1' => 10,
            'openTargetShifts2' => 1,

            'expectedFirstPerson' => self::PERSON1_FIRST,
        ];

        yield 'when max shifts week reached for nobody and availability is same and person2 has more open shifts' => [
            'maxShiftsWeekReached1' => true,
            'maxShiftsWeekReached2' => true,
            'availability1' => 'maybe',
            'availability2' => 'maybe',
            'openTargetShifts1' => 1,
            'openTargetShifts2' => 10,

            'expectedFirstPerson' => self::PERSON2_FIRST,
        ];
    }
}
