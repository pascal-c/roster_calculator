<?php

declare(strict_types=1);

namespace App\Service;

class ArrayService
{
    public function sortBy(array &$sortMe, callable $callback): void
    {
        usort(
            $sortMe,
            fn (mixed $element1, mixed $element2) => $callback($element1)
                <=>
                $callback($element2)
        );
    }
}
