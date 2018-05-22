<?php
/**
 * @copyright 2018 Martijn van der Ven
 * @license   0BSD
 */

declare(strict_types=1);

namespace Zegnat\Voting\Ranked;

use Equip\Structure\Set;

/**
 * An implementation of Instant-runoff voting.
 *
 * All ballots contain an ordered list of candidates, ordered by the voters preference.
 * The winner is the candidate who first reaches a majority. If there is no majority the
 * least prefered candidate has their ballots recounted, until there is a majority. Ties
 * are possible when there is still no majority between the final two candidates.
 *
 * @see https://en.wikipedia.org/wiki/Instant-runoff_voting
 */
final class IRV
{
    const FULL = 1;
    const OPTIONAL = 2;
    const STRICT = 4;

    /** @var array $candidates Every candidate. */
    private $candidates;

    /** @var int $variation The way ballots are validates during the election. */
    private $variation;

    /** @var array $ballots All valid ballots collected. */
    private $ballots = [];

    /**
     * Initialise the election amongst a group of candidates.
     *
     * @throws \InvalidArgumentException if an empty Set of canidates is provided.
     *
     * @param Set $candidates The possible candidates.
     * @param int $variation A choice between optional and full preferential voting. The
     *                       STRICT flag may be added to discard any ballots that include
     *                       invalid candidates, rather than silently ignoring them.
     */
    public function __construct(Set $candidates, int $variation = self::FULL)
    {
        if (count($candidates) === 0) {
            throw new \InvalidArgumentException('Cannot run an election with 0 candidates.');
        }
        $this->candidates = $candidates->toArray();
        $this->variation = $variation;
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
        $ballot = $ballot->toArray();
        $diff = count(array_diff($ballot, $this->candidates));
        if (($this->variation & self::STRICT) !== 0 && $diff !== 0 ||
            ($this->variation & self::FULL) !== 0 && count($ballot) - $diff !== count($this->candidates) ||
            ($this->variation & self::OPTIONAL) !== 0 && count($ballot) === $diff
        ) {
            return $this;
        }
        if ($diff > 0) {
            $ballot = array_values(array_intersect($ballot, $this->candidates));
        }
        $copy = clone $this;
        $copy->ballots[] = $ballot;
        return $copy;
    }

    /**
     * Determine the winner of the election.
     *
     * @return Set A collection containing one or more winners.
     */
    public function getWinner(): Set
    {
        $results = array_fill_keys($this->candidates, []);
        $pool = $this->ballots;
        while (count($pool) > 0) {
            foreach ($pool as $ballot) {
                $vote = array_shift($ballot);
                if ($vote !== null) {
                    $results[$vote][] = $ballot;
                }
            }
            usort($results, function ($a, $b): int {
                return ($a < $b) ? -1 : intval($a > $b);
            });
            $total = array_reduce($results, function ($carry, $item): int {
                return $carry + count($item);
            }, 0);
            if (count(reset($results)) > intdiv($total, 2)) {
                return new Set([key($results)]);
            } elseif (count($results) === 2) {
                return new Set(array_keys($results));
            } else {
                end($results);
                $last = key($results);
                $pool = $results[$last];
                $results[$last] = [];
            }
        }
        return new Set($this->candidates);
    }
}
