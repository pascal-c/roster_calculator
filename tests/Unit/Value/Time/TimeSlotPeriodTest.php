<?php

namespace Tests\Unit\Value\Time;

use App\Value\Time\TimeSlot;
use App\Value\Time\TimeSlotPeriod;

class TimeSlotPeriodTest extends \Codeception\Test\Unit
{
    public function testGetTimeSlotsForAll(): void
    {
        $date = new \DateTimeImmutable('2023-12-31');
        $timeSlotPeriod = new TimeSlotPeriod($date, TimeSlot::ALL);
        $timeSlots = $timeSlotPeriod->getTimeSlots();
        $this->assertCount(2, $timeSlots);
        $this->assertEquals($date, $timeSlots[0]->date);
        $this->assertEquals(TimeSlot::AM, $timeSlots[0]->daytime);
        $this->assertEquals($date, $timeSlots[1]->date);
        $this->assertEquals(TimeSlot::PM, $timeSlots[1]->daytime);
    }

    public function testGetTimeSlotsForPM(): void
    {
        $date = new \DateTimeImmutable('2023-12-31');
        $timeSlotPeriod = new TimeSlotPeriod($date, TimeSlot::PM);
        $timeSlots = $timeSlotPeriod->getTimeSlots();
        $this->assertCount(1, $timeSlots);
        $this->assertEquals($date, $timeSlots[0]->date);
        $this->assertEquals(TimeSlot::PM, $timeSlots[0]->daytime);
    }
}
