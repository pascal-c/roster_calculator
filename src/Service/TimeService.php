<?php

declare(strict_types=1);

namespace App\Service;

class TimeService
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
