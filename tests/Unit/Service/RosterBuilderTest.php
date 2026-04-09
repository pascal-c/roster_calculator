<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Entity\Availability;
use App\Entity\Location;
use App\Entity\LocationPreference;
use App\Entity\Person;
use App\Entity\RatingPointWeightings;
use App\Entity\Roster;
use App\Entity\Shift;
use App\Service\RosterBuilder;
use App\Service\TimeService;
use App\Value\Gender;
use App\Value\Status;
use App\Value\Time\TimeSlot;
use App\Value\Time\TimeSlotPeriod;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class RosterBuilderTest extends Unit
{
    private RosterBuilder $rosterBuilder;
    private TimeService&MockObject $timeService;

    public function _before(): void
    {
        $this->timeService = $this->createMock(TimeService::class);
        $this->rosterBuilder = new RosterBuilder($this->timeService);
    }

    public function testBuildNewCreatesRosterWithProperInitialization(): void
    {
        // data
        $now = new \DateTimeImmutable();
        $payload = [
            'people' => [],
            'locations' => [],
            'shifts' => [],
            'ratingPointWeightings' => [],
        ];

        // expectations
        $this->timeService
            ->method('now')
            ->willReturn($now);

        // execute
        $roster = $this->rosterBuilder->buildNew($payload);

        // assertions
        $this->assertInstanceOf(Roster::class, $roster);
        $this->assertNotNull($roster->getSlug());
        $this->assertSame(Status::NOT_STARTED->value, $roster->getStatus());
        $this->assertSame($payload, $roster->getPreconditions());
        $this->assertSame($now, $roster->getCreatedAt());
    }

    public function testBuildNewWithPeopleAddsPersonsToRoster(): void
    {
        // data
        $now = new \DateTimeImmutable();
        $date = new \DateTimeImmutable('2024-01-01');
        $payload = [
            'people' => [
                [
                    'id' => 'person1',
                    'gender' => 'male',
                    'constraints' => [
                        'wishedShiftsPerMonth' => 4,
                        'maxShiftsPerMonth' => 6,
                        'maxShiftsPerDay' => 1,
                        'targetShifts' => 5,
                        'blockedPeopleIds' => [],
                        'locationPreferences' => [],
                    ],
                    'availabilities' => [
                        [
                            'date' => $date->format('Y-m-d'),
                            'daytime' => TimeSlot::AM,
                            'availability' => Availability::YES,
                        ],
                    ],
                ],
            ],
            'locations' => [],
            'shifts' => [],
            'ratingPointWeightings' => [],
        ];

        // expectations
        $this->timeService
            ->method('now')
            ->willReturn($now);

        // execute
        $roster = $this->rosterBuilder->buildNew($payload);

        // assertions
        $person = $roster->getPerson('person1');
        $this->assertInstanceOf(Person::class, $person);
        $this->assertSame('person1', $person->id);
        $this->assertSame(Gender::MALE, $person->gender);
        $this->assertSame(4, $person->wishedShiftsPerMonth);
        $this->assertSame(6, $person->maxShiftsPerMonth);
        $this->assertSame(1, $person->maxShiftsPerDay);
        $this->assertSame(5, $person->targetShifts);
    }

    public function testBuildNewWithLocationsAddsLocationsToRoster(): void
    {
        // data
        $now = new \DateTimeImmutable();
        $payload = [
            'people' => [],
            'locations' => [
                [
                    'id' => 'location1',
                    'blockedPeopleIds' => [],
                ],
                [
                    'id' => 'location2',
                    'blockedPeopleIds' => [],
                ],
            ],
            'shifts' => [],
            'ratingPointWeightings' => [],
        ];

        // expectations
        $this->timeService
            ->method('now')
            ->willReturn($now);

        // execute
        $roster = $this->rosterBuilder->buildNew($payload);

        // assertions
        $location1 = $roster->getLocation('location1');
        $location2 = $roster->getLocation('location2');
        $this->assertInstanceOf(Location::class, $location1);
        $this->assertInstanceOf(Location::class, $location2);
        $this->assertSame('location1', $location1->id);
        $this->assertSame('location2', $location2->id);
    }

    public function testBuildNewWithShiftsAddsShiftsToRoster(): void
    {
        // data
        $now = new \DateTimeImmutable();
        $date = new \DateTimeImmutable('2024-01-01');
        $payload = [
            'people' => [],
            'locations' => [
                [
                    'id' => 'location1',
                    'blockedPeopleIds' => [],
                ],
            ],
            'shifts' => [
                [
                    'id' => 'shift1',
                    'date' => $date->format('Y-m-d'),
                    'daytime' => TimeSlotPeriod::AM,
                    'locationId' => 'location1',
                    'assignedPeople' => [],
                    'team' => [],
                ],
            ],
            'ratingPointWeightings' => [],
        ];

        // expectations
        $this->timeService
            ->method('now')
            ->willReturn($now);

        // execute
        $roster = $this->rosterBuilder->buildNew($payload);

        // assertions
        $shift = $roster->getShift('shift1');
        $this->assertInstanceOf(Shift::class, $shift);
        $this->assertSame('shift1', $shift->id);
        $this->assertSame(TimeSlotPeriod::AM, $shift->timeSlotPeriod->daytime);
    }

    public function testBuildNewWithLocationPreferencesAddsPreferencesToPerson(): void
    {
        // data
        $now = new \DateTimeImmutable();
        $date = new \DateTimeImmutable('2024-01-01');
        $payload = [
            'people' => [
                [
                    'id' => 'person1',
                    'gender' => 'male',
                    'constraints' => [
                        'wishedShiftsPerMonth' => 4,
                        'maxShiftsPerMonth' => 6,
                        'maxShiftsPerDay' => 1,
                        'targetShifts' => 5,
                        'blockedPeopleIds' => [],
                        'locationPreferences' => [
                            [
                                'locationId' => 'location1',
                                'points' => 10,
                            ],
                        ],
                    ],
                    'availabilities' => [],
                ],
            ],
            'locations' => [
                [
                    'id' => 'location1',
                    'blockedPeopleIds' => [],
                ],
            ],
            'shifts' => [],
            'ratingPointWeightings' => [],
        ];

        // expectations
        $this->timeService
            ->method('now')
            ->willReturn($now);

        // execute
        $roster = $this->rosterBuilder->buildNew($payload);

        // assertions
        $person = $roster->getPerson('person1');
        $location = $roster->getLocation('location1');
        $locationPreference = $person->getLocationPreferenceFor($location);
        $this->assertInstanceOf(LocationPreference::class, $locationPreference);
        $this->assertSame(10, $locationPreference->points);
    }

    public function testBuildNewWithBlockedPeopleAddsBlockedRelationships(): void
    {
        // data
        $now = new \DateTimeImmutable();
        $payload = [
            'people' => [
                [
                    'id' => 'person1',
                    'gender' => 'male',
                    'constraints' => [
                        'wishedShiftsPerMonth' => 4,
                        'maxShiftsPerMonth' => 6,
                        'maxShiftsPerDay' => 1,
                        'targetShifts' => 5,
                        'blockedPeopleIds' => ['person2'],
                        'locationPreferences' => [],
                    ],
                    'availabilities' => [],
                ],
                [
                    'id' => 'person2',
                    'gender' => 'female',
                    'constraints' => [
                        'wishedShiftsPerMonth' => 4,
                        'maxShiftsPerMonth' => 6,
                        'maxShiftsPerDay' => 1,
                        'targetShifts' => 5,
                        'blockedPeopleIds' => [],
                        'locationPreferences' => [],
                    ],
                    'availabilities' => [],
                ],
            ],
            'locations' => [],
            'shifts' => [],
            'ratingPointWeightings' => [],
        ];

        // expectations
        $this->timeService
            ->method('now')
            ->willReturn($now);

        // execute
        $roster = $this->rosterBuilder->buildNew($payload);

        // assertions
        $person1 = $roster->getPerson('person1');
        $person2 = $roster->getPerson('person2');
        $this->assertTrue($person1->isBlocked($person2));
    }

    public function testBuildNewSetsDefaultRatingPointWeightingsWhenNotProvided(): void
    {
        // data
        $now = new \DateTimeImmutable();
        $payload = [
            'people' => [],
            'locations' => [],
            'shifts' => [],
        ];

        // expectations
        $this->timeService
            ->method('now')
            ->willReturn($now);

        // execute
        $roster = $this->rosterBuilder->buildNew($payload);

        // assertions
        $weightings = $roster->getRatingPointWeightings();
        $this->assertInstanceOf(RatingPointWeightings::class, $weightings);
    }

    public function testBuildNewSetsCustomRatingPointWeightingsWhenProvided(): void
    {
        // data
        $now = new \DateTimeImmutable();
        $payload = [
            'people' => [],
            'locations' => [],
            'shifts' => [],
            'ratingPointWeightings' => [
                'pointsPerMissingPerson' => 10,
                'pointsPerMaxPerWeekExceeded' => 20,
                'pointsPerMaybePerson' => 5,
                'pointsPerTargetShiftsMissed' => 15,
                'pointsPerPersonNotInTeam' => 8,
            ],
        ];

        // expectations
        $this->timeService
            ->method('now')
            ->willReturn($now);

        // execute
        $roster = $this->rosterBuilder->buildNew($payload);

        // assertions
        $weightings = $roster->getRatingPointWeightings();
        $this->assertSame(10, $weightings->pointsPerMissingPerson);
        $this->assertSame(20, $weightings->pointsPerMaxPerWeekExceeded);
        $this->assertSame(5, $weightings->pointsPerMaybePerson);
        $this->assertSame(15, $weightings->pointsPerTargetShiftsMissed);
        $this->assertSame(8, $weightings->pointsPerPersonNotInTeam);
    }

    public function testBuildNewWithBundledShiftsCreatesBundleRelationship(): void
    {
        // data
        $now = new \DateTimeImmutable();
        $date = new \DateTimeImmutable('2024-01-01');
        $payload = [
            'people' => [],
            'locations' => [
                [
                    'id' => 'location1',
                    'blockedPeopleIds' => [],
                ],
            ],
            'shifts' => [
                [
                    'id' => 'shift1',
                    'date' => $date->format('Y-m-d'),
                    'daytime' => TimeSlotPeriod::AM,
                    'locationId' => 'location1',
                    'assignedPeople' => [],
                    'team' => [],
                    'bundleId' => 'bundle1',
                ],
                [
                    'id' => 'shift2',
                    'date' => $date->format('Y-m-d'),
                    'daytime' => TimeSlotPeriod::PM,
                    'locationId' => 'location1',
                    'assignedPeople' => [],
                    'team' => [],
                    'bundleId' => 'bundle1',
                ],
            ],
            'ratingPointWeightings' => [],
        ];

        // expectations
        $this->timeService
            ->method('now')
            ->willReturn($now);

        // execute
        $roster = $this->rosterBuilder->buildNew($payload);

        // assertions
        $shift1 = $roster->getShift('shift1');
        $shift2 = $roster->getShift('shift2');
        $this->assertSame([$shift2], $shift1->bundledShifts);
        $this->assertSame([$shift1], $shift2->bundledShifts);
    }

    public function testBuildFromRosterWithExistingRosterData(): void
    {
        // data
        $date = new \DateTimeImmutable('2024-01-01');
        $roster = new Roster();
        $roster->setPreconditions([
            'people' => [
                [
                    'id' => 'person1',
                    'gender' => 'male',
                    'constraints' => [
                        'wishedShiftsPerMonth' => 4,
                        'maxShiftsPerMonth' => 6,
                        'maxShiftsPerDay' => 1,
                        'targetShifts' => 5,
                        'blockedPeopleIds' => [],
                        'locationPreferences' => [],
                    ],
                    'availabilities' => [],
                ],
            ],
            'locations' => [
                [
                    'id' => 'location1',
                    'blockedPeopleIds' => [],
                ],
            ],
            'shifts' => [
                [
                    'id' => 'shift1',
                    'date' => $date->format('Y-m-d'),
                    'daytime' => TimeSlotPeriod::AM,
                    'locationId' => 'location1',
                    'assignedPeople' => [],
                    'team' => [],
                ],
            ],
            'ratingPointWeightings' => [],
        ]);

        // execute
        $this->rosterBuilder->buildFromRoster($roster);

        // assertions
        $person = $roster->getPerson('person1');
        $location = $roster->getLocation('location1');
        $shift = $roster->getShift('shift1');

        $this->assertInstanceOf(Person::class, $person);
        $this->assertInstanceOf(Location::class, $location);
        $this->assertInstanceOf(Shift::class, $shift);
    }
}
