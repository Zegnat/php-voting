<?php

declare(strict_types=1);

namespace Zegnat\Voting\Ranked;

use Equip\Structure\Set;
use PHPUnit\Framework\TestCase;

final class IRVTest extends TestCase
{
    public function testCannotBeCreatedWithoutArguments(): void
    {
        $this->expectException(\ArgumentCountError::class);
        new IRV();
    }

    public function testCannotBeCreatedWithZeroCandidates(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new IRV(new Set());
    }

    public function testCanBeCreatedWithCandidate(): IRV
    {
        $instance = new IRV(new Set(['Alice']));
        $this->assertInstanceOf(IRV::class, $instance);
        return $instance;
    }

    public function testCanBeCreatedWithMultipleCandidates(): IRV
    {
        $instance = new IRV(new Set(['Alice', 'Bob']));
        $this->assertInstanceOf(IRV::class, $instance);
        return $instance;
    }
}
