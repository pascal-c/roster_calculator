<?php

namespace Tests\Unit\Entity;

use App\Entity\Availability;
use App\Entity\Person;
use App\Value\Gender;
use App\Value\Time\TimeSlot;
use App\Value\Time\TimeSlotPeriod;

class PersonTest extends \Codeception\Test\Unit
{
    public function testIsAvailableOnWithTimeSlot()
    {
        $person = new Person('1', Gender::FEMALE, 1, 1, 1, 1, 1);
        $goodTimeSlot = new TimeSlot(new \DateTimeImmutable('2021-01-01'), TimeSlot::AM);
        $unavailableTimeSlot = new TimeSlot(new \DateTimeImmutable('2021-01-02'), TimeSlot::AM);
        $person->addAvailability(new Availability($goodTimeSlot, Availability::MAYBE));
        $person->addAvailability(new Availability($unavailableTimeSlot, Availability::NO));

        $this->assertTrue($person->isAvailableOn($goodTimeSlot));
        $this->assertFalse($person->isAvailableOn($unavailableTimeSlot));

        $missingTimeSlot = new TimeSlot(new \DateTimeImmutable('2021-01-01'), TimeSlot::PM);
        $this->assertFalse($person->isAvailableOn($missingTimeSlot));
    }

    public function testIsAvailableOnWithTimeSlotPeriod()
    {
        $person = new Person('1', Gender::FEMALE, 1, 1, 1, 1, 1);

        $date = new \DateTimeImmutable('2021-01-01');
        $timeSlot = new TimeSlot($date, TimeSlot::AM);
        $person->addAvailability(new Availability($timeSlot, Availability::MAYBE));

        $timeSlotPeriod = new TimeSlotPeriod($date, TimeSlotPeriod::ALL);
        $this->assertFalse($person->isAvailableOn($timeSlotPeriod));

        $timeSlot = new TimeSlot($date, TimeSlot::PM);
        $person->addAvailability(new Availability($timeSlot, Availability::YES));
        $this->assertTrue($person->isAvailableOn($timeSlotPeriod));
    }

    public function testGetAvailabilityOn()
    {
        $person = new Person('1', Gender::FEMALE, 1, 1, 1, 1, 1);
        $date = new \DateTimeImmutable('2021-01-01');
        $person->addAvailability(new Availability(new TimeSlot($date, TimeSlot::AM), Availability::MAYBE));

        $timeSlotPeriod = new TimeSlotPeriod($date, TimeSlotPeriod::ALL);
        $this->assertSame(Availability::NO, $person->getAvailabilityOn($timeSlotPeriod));

        $person->addAvailability(new Availability(new TimeSlot($date, TimeSlot::PM), Availability::YES));
        $this->assertSame(Availability::MAYBE, $person->getAvailabilityOn($timeSlotPeriod));
    }
}
