<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Availability;
use App\Entity\Location;
use App\Entity\Person;
use App\Entity\RatingPointWeightings;
use App\Entity\Roster;
use App\Entity\Shift;
use App\Value\Gender;
use App\Value\Status;
use App\Value\Time\TimeSlot;
use App\Value\Time\TimeSlotPeriod;
use Ramsey\Uuid\Uuid;

class RosterBuilder
{
    public function __construct(private TimeService $timeService)
    {
    }

    public function buildNew(array $payload): Roster
    {
        $roster = new Roster();
        $roster->setSlug(Uuid::uuid4()->toString());
        $roster->setStatus(Status::NOT_STARTED->value);
        $roster->setPreconditions($payload);
        $roster->setCreatedAt($this->timeService->now());

        $this->buildFromRoster($roster);

        return $roster;
    }

    public function buildFromRoster(Roster $roster): void
    {
        $payload = $roster->getPreconditions();

        foreach ($payload['people'] as $personPayload) {
            $this->addPerson($personPayload, $roster);
        }

        foreach ($payload['locations'] as $locationPayload) {
            $this->addLocation($locationPayload, $roster);
        }

        foreach ($payload['shifts'] as $shiftPayload) {
            $this->addShift($shiftPayload, $roster);
        }

        $this->setRatingPointWeightings($payload['ratingPointWeightings'] ?? [], $roster);
    }

    private function addPerson(array $personPayload, Roster $roster): void
    {
        $person = new Person(
            id: $personPayload['id'],
            gender: Gender::from($personPayload['gender']),
            wishedShiftsPerMonth: $personPayload['constraints']['wishedShiftsPerMonth'],
            maxShiftsPerMonth: $personPayload['constraints']['maxShiftsPerMonth'],
            maxShiftsPerWeek: $personPayload['constraints']['maxShiftsPerWeek'] ?? null,
            maxShiftsPerDay: $personPayload['constraints']['maxShiftsPerDay'],
            targetShifts: $personPayload['constraints']['targetShifts'],
        );
        foreach ($personPayload['availabilities'] as $availabilityPayload) {
            $availability = new Availability(
                timeSlot: new TimeSlot(
                    date: new \DateTimeImmutable($availabilityPayload['date']),
                    daytime: $availabilityPayload['daytime'],
                ),
                availability: $availabilityPayload['availability'],
            );
            $person->addAvailability($availability);
        }

        $roster->addPerson($person);
    }

    private function addLocation(array $locationPayload, Roster $roster): void
    {
        $blockedPeople = array_map(
            fn (string $personId): Person => $roster->getPerson($personId),
            $locationPayload['blockedPeople'] ?? [],
        );
        $location = new Location($locationPayload['id'], $blockedPeople);

        $roster->addLocation($location);
    }

    private function addShift(array $shiftPayload, Roster $roster): void
    {
        $assignedPeople = array_map(
            fn (string $id): Person => $roster->getPerson($id),
            $shiftPayload['personIds'] ?? [],
        );
        $shift = new Shift(
            id: $shiftPayload['id'],
            timeSlotPeriod: new TimeSlotPeriod(
                date: new \DateTimeImmutable($shiftPayload['date']),
                daytime: $shiftPayload['daytime'],
            ),
            location: $roster->getLocation($shiftPayload['locationId'] ?? null),
            assignedPeople: $assignedPeople,
        );

        $roster->addShift($shift);
    }

    private function setRatingPointWeightings(array $ratingPayload, Roster $roster): void
    {
        if (empty($ratingPayload)) {
            $ratingPointWeights = new RatingPointWeightings();
        } else {
            $ratingPointWeights = new RatingPointWeightings(
                pointsPerMissingPerson: $ratingPayload['pointsPerMissingPerson'],
                pointsPerMaxPerWeekExceeded: $ratingPayload['pointsPerMaxPerWeekExceeded'],
                pointsPerMaybePerson: $ratingPayload['pointsPerMaybePerson'],
                pointsPerTargetShiftsMissed: $ratingPayload['pointsPerTargetShiftsMissed'],
            );
        }

        $roster->setRatingPointWeightings($ratingPointWeights);
    }
}
