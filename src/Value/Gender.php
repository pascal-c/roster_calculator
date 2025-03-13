<?php

declare(strict_types=1);

namespace App\Value;

enum Gender: string
{
    case DIVERSE = 'diverse';
    case FEMALE = 'female';
    case MALE = 'male';

    public function isMale(): bool
    {
        return self::MALE === $this;
    }
}
