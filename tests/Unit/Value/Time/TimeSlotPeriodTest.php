<?php

namespace Tests\Unit\Value\Time;

use App\Value\Time\TimeSlot;
use App\Value\Time\TimeSlotPeriod;

class TimeSlotPeriodTest extends \Codeception\Test\Unit
{
    public function testGenerateTimeSlotsForAll(): void
    {
        $date = new \DateTimeImmutable('2023-12-31');
        $timeSlotPeriod = new TimeSlotPeriod($date, TimeSlot::ALL);
        $timeSlots = $timeSlotPeriod->timeSlots;
        $this->assertCount(2, $timeSlots);
        $this->assertEquals($date, $timeSlots[0]->date);
        $this->assertEquals(TimeSlot::AM, $timeSlots[0]->daytime);
        $this->assertEquals($date, $timeSlots[1]->date);
        $this->assertEquals(TimeSlot::PM, $timeSlots[1]->daytime);
    }

    public function testGenerateTimeSlotsForPM(): void
    {
        $date = new \DateTimeImmutable('2023-12-31');
        $timeSlotPeriod = new TimeSlotPeriod($date, TimeSlot::PM);
        $timeSlots = $timeSlotPeriod->timeSlots;
        $this->assertCount(1, $timeSlots);
        $this->assertEquals($date, $timeSlots[0]->date);
        $this->assertEquals(TimeSlot::PM, $timeSlots[0]->daytime);
    }

    public function testGenerateWeekId(): void
    {
        $date = new \DateTimeImmutable('2017-01-01');
        $timeSlotPeriod = new TimeSlotPeriod($date, TimeSlot::ALL);
        $this->assertEquals('2016-52', $timeSlotPeriod->weekId);

        $date = new \DateTimeImmutable('2017-01-02');
        $timeSlotPeriod = new TimeSlotPeriod($date, TimeSlot::ALL);
        $this->assertEquals('2017-01', $timeSlotPeriod->weekId);
    }
}
