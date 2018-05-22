<?php
/**
 * @copyright 2018 Martijn van der Ven
 * @license   0BSD
 */

declare(strict_types=1);

namespace Zegnat\Voting\Plurality;

use Equip\Structure\Set;

/**
 * An implementation of First-past-the-post voting.
 *
 * All ballots contain exactly one of the possible candidates. The winner is the candidate
 * with the most ballots. Ties are possible if several candidates get the same amount
 * of ballots.
 *
 * @see https://en.wikipedia.org/wiki/First-past-the-post_voting
 */
final class FPTP
{
    /** @var array $candidates Every candidate expressed by array keys. */
    private $candidates;

    /** @var array $ballots All valid ballots collected. */
    private $ballots = [];

    /**
     * Initialise the election amongst a group of candidates.
     *
     * @throws \InvalidArgumentException if an empty Set of canidates is provided.
     *
     * @param Set $candidates The possible candidates.
     */
    public function __construct(Set $candidates)
    {
        if (count($candidates) === 0) {
            throw new \InvalidArgumentException('Cannot run an election with 0 candidates.');
        }
        $this->candidates = array_fill_keys($candidates->toArray(), 0);
    }

    /**
     * Add a ballot to the election.
     *
     * @param Set $ballot The ballot expressing a vote.
     *
     * @return self If the ballot is valid this returns a new copy of the election, else
     *              it returns the current instance unchanged.
     */
    public function cast(Set $ballot): self
    {
        if (count($ballot) === 1 && isset($this->candidates[$ballot[0]])) {
            $copy = clone $this;
            $copy->ballots[] = $ballot;
            return $copy;
        }
        return $this;
    }

    /**
     * Determine the winner of the election.
     *
     * @return Set A collection containing one or more winners.
     */
    public function getWinner(): Set
    {
        $results = $this->candidates;
        foreach ($this->ballots as $ballot) {
            $results[$ballot[0]] += 1;
        }
        $max = max($results);
        return new Set(array_keys(array_filter($results, function ($candidate) use ($max): bool {
            return $candidate === $max;
        })));
    }
}
