<?php

declare(strict_types=1);

namespace App\Entity;

class LocationPreference
{
    public function __construct(
        public readonly ?Location $location,
        public readonly int $points = 0,
    ) {
    }
}
