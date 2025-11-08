<?php

namespace Tests\Unit\Entity;

use App\Entity\Availability;
use App\Entity\Location;
use App\Entity\LocationPreference;
use App\Entity\Person;
use App\Value\Gender;
use App\Value\Time\TimeSlot;
use App\Value\Time\TimeSlotPeriod;

class PersonTest extends \Codeception\Test\Unit
{
    private Person $person;

    public function _before(): void
    {
        $this->person = new Person('1', Gender::FEMALE, 1, 1, 1, 1, 1, locationPreferenceDefaultPoints: 1);
    }

    public function testIsAvailableOnWithTimeSlot()
    {
        $goodTimeSlot = new TimeSlot(new \DateTimeImmutable('2021-01-01'), TimeSlot::AM);
        $unavailableTimeSlot = new TimeSlot(new \DateTimeImmutable('2021-01-02'), TimeSlot::AM);
        $this->person->addAvailability(new Availability($goodTimeSlot, Availability::MAYBE));
        $this->person->addAvailability(new Availability($unavailableTimeSlot, Availability::NO));

        $this->assertTrue($this->person->isAvailableOn($goodTimeSlot));
        $this->assertFalse($this->person->isAvailableOn($unavailableTimeSlot));

        $missingTimeSlot = new TimeSlot(new \DateTimeImmutable('2021-01-01'), TimeSlot::PM);
        $this->assertFalse($this->person->isAvailableOn($missingTimeSlot));
    }

    public function testIsAvailableOnWithTimeSlotPeriod()
    {
        $date = new \DateTimeImmutable('2021-01-01');
        $timeSlot = new TimeSlot($date, TimeSlot::AM);
        $this->person->addAvailability(new Availability($timeSlot, Availability::MAYBE));

        $timeSlotPeriod = new TimeSlotPeriod($date, TimeSlotPeriod::ALL);
        $this->assertFalse($this->person->isAvailableOn($timeSlotPeriod));

        $timeSlot = new TimeSlot($date, TimeSlot::PM);
        $this->person->addAvailability(new Availability($timeSlot, Availability::YES));
        $this->assertTrue($this->person->isAvailableOn($timeSlotPeriod));
    }

    public function testGetAvailabilityOn()
    {
        $date = new \DateTimeImmutable('2021-01-01');
        $this->person->addAvailability(new Availability(new TimeSlot($date, TimeSlot::AM), Availability::MAYBE));

        $timeSlotPeriod = new TimeSlotPeriod($date, TimeSlotPeriod::ALL);
        $this->assertSame(Availability::NO, $this->person->getAvailabilityOn($timeSlotPeriod));

        $this->person->addAvailability(new Availability(new TimeSlot($date, TimeSlot::PM), Availability::YES));
        $this->assertSame(Availability::MAYBE, $this->person->getAvailabilityOn($timeSlotPeriod));
    }

    public function testGetLocationPreferenceFor()
    {
        $location1 = new Location('locaction1');
        $location2 = new Location('locaction2');
        $preference1 = new LocationPreference($location1, 5);
        $this->person->addLocationPreference($preference1);

        $this->assertSame(5, $this->person->getLocationPreferenceFor($location1)->points); // 5 was explicitly set
        $this->assertSame(1, $this->person->getLocationPreferenceFor($location2)->points); // default points 1

        $this->assertSame($preference1, $this->person->getLocationPreferenceFor($location1));
        $this->assertEquals(new LocationPreference($location2, 1), $this->person->getLocationPreferenceFor($location2));
    }
}
