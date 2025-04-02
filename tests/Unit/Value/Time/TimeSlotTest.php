<?php

namespace Tests\Unit\Value\Time;

use App\Value\Time\TimeSlot;

class TimeSlotTest extends \Codeception\Test\Unit
{
    public function testGetDateIndex()
    {
        $timeSlot = new TimeSlot(new \DateTimeImmutable('2021-01-31'), TimeSlot::AM);
        $this->assertSame('2021-01-31', $timeSlot->dateIndex);
    }

    public function testConstructWithInvalidDaytime()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('all is not a valid daytime for a App\Value\Time\TimeSlot');
        new TimeSlot(new \DateTimeImmutable('2021-01-31'), 'all');
    }
}
