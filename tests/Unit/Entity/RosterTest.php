<?php

namespace Tests\Unit\Entity;

use App\Entity\Location;
use App\Entity\Person;
use App\Entity\Roster;
use App\Entity\Shift;
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
        $shift = Stub::make(Shift::class);
        $roster->addShift($shift);
        $this->assertSame($shift, $roster->getShifts()[0]);
    }

    public function testAddLocation()
    {
        $roster = new Roster();
        $location = Stub::makeEmpty(Location::class, ['id' => '72']);
        $roster->addLocation($location);
        $this->assertSame($location, $roster->getLocation('72'));
    }
}
