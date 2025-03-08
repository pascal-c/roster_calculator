<?php

declare(strict_types=1);

namespace App\Entity;

class Location
{
    public function __construct(
        public readonly string $id,
    ) {
    }
}
