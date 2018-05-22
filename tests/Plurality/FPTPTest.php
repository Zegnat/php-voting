<?php

declare(strict_types=1);

namespace Zegnat\Voting\Plurality;

use Equip\Structure\Set;
use PHPUnit\Framework\TestCase;

final class FPTPTest extends TestCase
{
    public function testCannotBeCreatedWithoutArguments(): void
    {
        $this->expectException(\ArgumentCountError::class);
        new FPTP();
    }

    public function testCannotBeCreatedWithZeroCandidates(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new FPTP(new Set());
    }

    public function testCanBeCreatedWithCandidate(): FPTP
    {
        $instance = new FPTP(new Set(['Alice']));
        $this->assertInstanceOf(FPTP::class, $instance);
        return $instance;
    }

    /**
     * @depends testCanBeCreatedWithCandidate
     */
    public function testCastingBallotForNonExistingCandidateIsIgnored($election): void
    {
        $ballot = new Set(['Bob']);
        $new = $election->cast($ballot);
        $this->assertSame($election, $new);
    }

    /**
     * @depends testCanBeCreatedWithCandidate
     */
    public function testCastingInvalidBallotIsIgnored($election): void
    {
        $ballot = new Set(['Bob', 'Carol']);
        $new = $election->cast($ballot);
        $this->assertSame($election, $new);
    }

    /**
     * @depends testCanBeCreatedWithCandidate
     */
    public function testCastingBallotForCandidateReturnsNewInstance($election): FPTP
    {
        $ballot = new Set(['Alice']);
        $new = $election->cast($ballot);
        $this->assertNotSame($election, $new);
        return $new;
    }

    /**
     * @depends testCanBeCreatedWithCandidate
     */
    public function testWinnerCanBeDeterminedWithoutVotes($election): void
    {
        $this->assertSame($election->getWinner()->toArray(), ['Alice']);
    }

    /**
     * @depends testCastingBallotForCandidateReturnsNewInstance
     */
    public function testWinnerCanBeDeterminedWithVotes($election): void
    {
        $this->assertSame($election->getWinner()->toArray(), ['Alice']);
    }

    public function testCanBeCreatedWithMultipleCandidates(): FPTP
    {
        $instance = new FPTP(new Set(['Alice', 'Bob']));
        $this->assertInstanceOf(FPTP::class, $instance);
        return $instance;
    }

    /**
     * @depends testCanBeCreatedWithMultipleCandidates
     */
    public function testWinnersCanBeDeterminedWithoutVotes($election): void
    {
        $this->assertSame($election->getWinner()->toArray(), ['Alice', 'Bob']);
    }

    /**
     * @depends testCanBeCreatedWithMultipleCandidates
     */
    public function testTieCanHappen($election): void
    {
        $election = $election->cast(new Set(['Alice']))->cast(new Set(['Bob']));
        $this->assertSame($election->getWinner()->toArray(), ['Alice', 'Bob']);
    }

    /**
     * @depends testCanBeCreatedWithMultipleCandidates
     * @depends testWinnerCanBeDeterminedWithVotes
     * @depends testCastingBallotForCandidateReturnsNewInstance
     * @see https://en.wikipedia.org/wiki/First-past-the-post_voting#Illustration
     */
    public function testSimulatePoll(): void
    {
        $votes = [
            'Tony Tan' => 745693,
            'Tan Cheng Bock' => 738311,
            'Tan Jee Say' => 530441,
            'Tan Kin Lian' => 104095,
        ];
        $election = new FPTP(new Set(array_keys($votes)));
        foreach ($votes as $candidate => $count) {
            $count = intdiv($count, 10000);
            while ($count > 0) {
                $election = $election->cast(new Set([$candidate]));
                $count -= 1;
            }
        }
        $this->assertSame($election->getWinner()->toArray(), ['Tony Tan']);
    }
}
