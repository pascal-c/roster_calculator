<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Service\ArrayService;
use Codeception\Test\Unit;

final class ArrayServiceTest extends Unit
{
    private ArrayService $arrayService;

    public function testSortBy(): void
    {
        // data
        $array = [
            $a = ['x' => 33, 'y' => 11, 'z' => 33],
            $b = ['x' => 22, 'y' => 22, 'z' => 11],
            $c = ['x' => 11, 'y' => 33, 'z' => 22],
        ];

        // services
        $this->arrayService = new ArrayService();

        // sort by x
        $this->arrayService->sortBy($array, fn (array $element): int => $element['x']);
        $this->assertSame([$c, $b, $a], $array);

        // sort by y
        $this->arrayService->sortBy($array, fn (array $element): int => $element['y']);
        $this->assertSame([$a, $b, $c], $array);

        // sort by z
        $this->arrayService->sortBy($array, fn (array $element): int => $element['z']);
        $this->assertSame([$b, $c, $a], $array);
    }
}
