<?php

declare(strict_types=1);

namespace App\Entity;

use App\Value\Gender;
use App\Value\Time\TimeSlotPeriod;

class Person
{
    private array $availabilities = [];
    private array $locationPreferences = [];

    public function __construct(
        public readonly string $id,
        public readonly Gender $gender,
        public readonly int $wishedShiftsPerMonth,
        public readonly int $maxShiftsPerMonth,
        public readonly ?int $maxShiftsPerWeek,
        public readonly int $maxShiftsPerDay,
        public readonly int $targetShifts,
    ) {
    }

    public function addAvailability(Availability $availability): void
    {
        $this->availabilities[$availability->timeSlot->dateIndex][$availability->timeSlot->daytime] = $availability;
    }

    public function isAvailableOn(TimeSlotPeriod $timeSlotPeriod): bool
    {
        return Availability::NO !== $this->getAvailabilityOn($timeSlotPeriod);
    }

    public function getAvailabilityOn(TimeSlotPeriod $timeSlotPeriod): string
    {
        $result = Availability::YES;
        foreach ($timeSlotPeriod->timeSlots as $timeSlot) {
            $availability = $this->availabilities[$timeSlot->dateIndex][$timeSlot->daytime] ?? null;

            if (null === $availability || Availability::NO === $availability->availability) {
                return Availability::NO;
            } elseif (Availability::MAYBE === $availability->availability) {
                $result = Availability::MAYBE;
            }
        }

        return $result;
    }

    public function addLocationPreference(LocationPreference $locationPreference): void
    {
        $this->locationPreferences[$locationPreference->location?->id] = $locationPreference;
    }

    public function getLocationPreferenceFor(?Location $location): LocationPreference
    {
        if (!array_key_exists($location?->id, $this->locationPreferences)) {
            $this->addLocationPreference(new LocationPreference($location));
        }

        return $this->locationPreferences[$location?->id];
    }
}
