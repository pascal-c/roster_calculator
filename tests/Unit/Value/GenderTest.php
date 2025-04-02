<?php

namespace Tests\Unit\Value;

use App\Value\Gender;

class TimeSlotTest extends \Codeception\Test\Unit
{
    public function testIsMale(): void
    {
        $male = Gender::MALE;
        $diverse = Gender::DIVERSE;
        $female = Gender::FEMALE;
        $this->assertTrue($male->isMale());
        $this->assertFalse($diverse->isMale());
        $this->assertFalse($female->isMale());
    }
}
