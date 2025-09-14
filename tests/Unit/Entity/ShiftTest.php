<?php

namespace Tests\Unit\Entity;

use App\Entity\Person;
use App\Entity\Shift;
use App\Value\Time\TimeSlot;

class ShiftTest extends \Codeception\Test\Unit
{
    public function testStillNeededPeople()
    {
        $shift = new Shift('1', $this->make(TimeSlot::class), null, [$this->make(Person::class)], totalNeededPeople: 3);
        $this->assertSame(2, $shift->stillNeededPeople);
    }
}
