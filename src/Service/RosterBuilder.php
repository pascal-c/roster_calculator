<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Availability;
use App\Entity\Location;
use App\Entity\Person;
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

    public function __invoke(array $payload): Roster
    {
        $roster = new Roster();
        $roster->setSlug(Uuid::uuid4()->toString());
        $roster->setStatus(Status::NOT_STARTED->value);
        $roster->setPreconditions($payload);
        $roster->setCreatedAt($this->timeService->now());

        foreach ($payload['locations'] as $locationPayload) {
            $location = new Location($locationPayload['id']);
            $roster->addLocation($location);
        }

        foreach ($payload['people'] as $personPayload) {
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

        foreach ($payload['shifts'] as $shiftPayload) {
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

        return $roster;
    }
}
