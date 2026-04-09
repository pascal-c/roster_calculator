<?php

namespace Tests\Unit\Entity;

use App\Entity\Location;
use App\Entity\Person;
use App\Entity\Roster;
use App\Entity\Shift;
use App\Value\Time\TimeSlotPeriod;
use Codeception\Stub;

class RosterTest extends \Codeception\Test\Unit
{
    public function testAddPerson()
    {
        $roster = new Roster();
        $person = Stub::make(Person::class, ['id' => '1']);
        $roster->addPerson($person);
        $this->assertSame($person, $roster->getPerson('1'));
    }

    public function testAddShift()
    {
        $roster = new Roster();
        $shift = Stub::make(Shift::class, ['id' => '1', 'timeSlotPeriod' => new TimeSlotPeriod(new \DateTimeImmutable('2024-12-28'), TimeSlotPeriod::ALL)]);
        $roster->addShift($shift);
        $this->assertSame($shift, $roster->getShifts()[0]);
        $this->assertSame(1, $roster->countShifts());
        $this->assertSame(['2024-52'], $roster->getWeekIds());

        $shift2 = Stub::make(Shift::class, ['id' => '2', 'timeSlotPeriod' => new TimeSlotPeriod(new \DateTimeImmutable('2024-12-28'), TimeSlotPeriod::PM)]);
        $roster->addShift($shift2);
        $this->assertSame($shift2, $roster->getShifts()[1]);
        $this->assertSame(2, $roster->countShifts());
        $this->assertSame(['2024-52'], $roster->getWeekIds());

        $shift3 = Stub::make(Shift::class, ['id' => '3', 'timeSlotPeriod' => new TimeSlotPeriod(new \DateTimeImmutable('2024-12-31'), TimeSlotPeriod::PM)]);
        $roster->addShift($shift3);
        $this->assertSame($shift3, $roster->getShifts()[2]);
        $this->assertSame(3, $roster->countShifts());
        $this->assertSame(['2024-52', '2025-01'], $roster->getWeekIds());

        // test getShift with index
        $this->assertSame($shift, $roster->getShift('1'));
        $this->assertSame($shift2, $roster->getShift('2'));
        $this->assertSame($shift3, $roster->getShift('3'));
        $this->assertNull($roster->getShift('non-existing'));
    }

    public function testAddLocation()
    {
        $roster = new Roster();
        $location = Stub::makeEmpty(Location::class, ['id' => '72']);
        $roster->addLocation($location);
        $this->assertSame($location, $roster->getLocation('72'));
    }
}
