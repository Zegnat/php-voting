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

    public function testCanBeCreatedWithCandidates(): void
    {
        $this->assertInstanceOf(IRV::class, new IRV(new Set(['Alice', 'Bob', 'Carol'])));
    }

    public function testFullPreferentialVotingIgnoresIncompleteBallots(): void
    {
        $election = new IRV(new Set(['Alice', 'Bob', 'Carol']), IRV::FULL);
        $new = $election->cast(new Set(['Carol', 'Bob']));
        $this->assertSame($election, $new);
    }

    public function testFullPreferentialVotingAcceptsCompleteBallots(): void
    {
        $election = new IRV(new Set(['Alice', 'Bob', 'Carol']), IRV::FULL);
        $new = $election->cast(new Set(['Carol', 'Bob', 'Alice']));
        $this->assertNotSame($election, $new);
    }

    public function testFullPreferentialVotingAcceptsCompleteBallotsWithExtras(): void
    {
        $election = new IRV(new Set(['Alice', 'Bob', 'Carol']), IRV::FULL);
        $new = $election->cast(new Set(['Carol', 'Bob', 'Alice', 'None']));
        $this->assertNotSame($election, $new);
    }

    public function testStrictFullPreferentialVotingIgnoresCompleteBallotsWithExtras(): void
    {
        $election = new IRV(new Set(['Alice', 'Bob', 'Carol']), IRV::FULL | IRV::STRICT);
        $new = $election->cast(new Set(['Carol', 'Bob', 'Alice', 'None']));
        $this->assertSame($election, $new);
    }

    public function testOptionalPreferentialVotingAcceptsIncompleteBallots(): void
    {
        $election = new IRV(new Set(['Alice', 'Bob', 'Carol']), IRV::OPTIONAL);
        $new = $election->cast(new Set(['Carol', 'Bob']));
        $this->assertNotSame($election, $new);
    }

    public function testOptionalPreferentialVotingAcceptsCompleteBallots(): void
    {
        $election = new IRV(new Set(['Alice', 'Bob', 'Carol']), IRV::OPTIONAL);
        $new = $election->cast(new Set(['Carol', 'Bob', 'Alice']));
        $this->assertNotSame($election, $new);
    }

    public function testOptionalPreferentialVotingAcceptsBallotsWithExtras(): void
    {
        $election = new IRV(new Set(['Alice', 'Bob', 'Carol']), IRV::OPTIONAL);
        $new = $election->cast(new Set(['Carol', 'Bob', 'Alice', 'None']));
        $this->assertNotSame($election, $new);
    }

    public function testStrictOptionalPreferentialVotingIgnoresBallotsWithExtras(): void
    {
        $election = new IRV(new Set(['Alice', 'Bob', 'Carol']), IRV::OPTIONAL | IRV::STRICT);
        $new = $election->cast(new Set(['Carol', 'Bob', 'None']));
        $this->assertSame($election, $new);
    }

    /**
     * @depends testCanBeCreatedWithCandidates
     * @depends testFullPreferentialVotingAcceptsCompleteBallots
     * @see https://en.wikipedia.org/wiki/Instant-runoff_voting#Five_voters,_three_candidates
     */
    public function testSimulateFiveVotersThreeCandidates(): void
    {
        $election = new IRV(new Set(['Bob', 'Sue', 'Bill']));
        $votes = [
            'a' => ['Bob', 'Bill', 'Sue'],
            'b' => ['Sue', 'Bob', 'Bill'],
            'c' => ['Bill', 'Sue', 'Bob'],
            'd' => ['Bob', 'Bill', 'Sue'],
            'e' => ['Sue', 'Bob', 'Bill'],
        ];
        foreach ($votes as $ballot) {
            $election = $election->cast(new Set($ballot));
        }
        $this->assertSame(['Sue'], $election->getWinner()->toArray());
    }

    /**
     * @depends testCanBeCreatedWithCandidates
     * @depends testFullPreferentialVotingAcceptsCompleteBallots
     * @see https://en.wikipedia.org/wiki/Instant-runoff_voting#Tennessee_capital_election
     */
    public function testSimulateTennesseeCapitalElection(): void
    {
        $election = new IRV(new Set(['Memphis', 'Nashville', 'Knoxville', 'Chattanooga']));
        $votes = [
            42 => ['Memphis', 'Nashville', 'Chattanooga', 'Knoxville'],
            26 => ['Nashville', 'Chattanooga', 'Knoxville', 'Memphis'],
            15 => ['Chattanooga', 'Knoxville', 'Nashville', 'Memphis'],
            17 => ['Knoxville', 'Chattanooga', 'Nashville', 'Memphis'],
        ];
        foreach ($votes as $times => $ballot) {
            while ($times > 0) {
                $election = $election->cast(new Set($ballot));
                $times -= 1;
            }
        }
        $this->assertSame(['Knoxville'], $election->getWinner()->toArray());
    }
}
