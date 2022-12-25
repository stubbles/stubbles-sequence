<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * The contents of this file draw heavily from XP Framework
 * https://github.com/xp-forge/sequence
 *
 * Copyright (c) 2001-2014, XP-Framework Team
 * All rights reserved.
 * https://github.com/xp-framework/xp-framework/blob/master/core/src/main/php/LICENCE
 */
namespace stubbles\sequence;
/**
 * A collector accumulates elements into a structure, optionally transforming the
 * result into a final representation.
 *
 * @since 5.2.0
 * @template T
 */
class Collector
{
    /**
     * returns a fresh structure to collect elements into
     *
     * @var callable
     */
    private $supplier;
    /**
     * structure to collect elements into
     */
    private mixed $structure;
    /**
     * accumulates elements into structure
     *
     * @var callable
     */
    private $accumulator;
    /**
     * final operation after all elements have been added to the structure
     *
     * @var callable|null
     */
    private $finisher;

    /**
     * constructor
     *
     * @param callable $supplier    returns a fresh structure to collect elements into
     * @param callable $accumulator accumulates elements into structure
     * @param callable $finisher    optional final operation after all elements have been added to the structure
     */
    public function __construct(
        callable $supplier,
        callable $accumulator,
        ?callable $finisher = null)
    {
        $this->supplier    = $supplier;
        $this->structure   = $supplier();
        $this->accumulator = $accumulator;
        $this->finisher    = $finisher;
    }

    /**
     * returns a collector for lists
     *
     * @api
     * @return Collector<array<int,mixed>>
     */
    public static function forList(): self
    {
        return new self(
            fn() => [],
            /**
             * @param mixed[] $list
             */
            function(array &$list, mixed $element): void { $list[] = $element; }
        );
    }

    /**
     * returns a collector for maps
     *
     * @api
     * @return Collector<array>
     */
    public static function forMap(
        ?callable $keySelector = null,
        ?callable $valueSelector = null
    ): self {
        $selectKey   = $keySelector ?: fn($value, $key) => $key;
        $selectValue = $valueSelector ?: fn($value, $key) => $value;
        return new self(
            function(): array { return []; },
            /**
             * @param array<string,mixed> $map
             */
            function(array &$map, mixed $element, int|string $key) use($selectKey, $selectValue): void
            {
                $map[$selectKey($element, $key)] = $selectValue($element, $key);
            }
        );
    }

    /**
     * returns a collector to sum up elements
     *
     * @api
     * @param  callable $num callable which retrieves a number from a given element
     * @return Collector<float>
     */
    public static function forSum(callable $num): self {
        return new self(
            fn() => 0,
            function(int|float &$result, mixed $element) use($num): void { $result+= $num($element); }
        );
    }

    /**
     * returns a collector to calculate an average for all the given elements
     *
     * @api
     * @param  callable $num callable which retrieves a number from a given element
     * @return Collector<float>
     */
    public static function forAverage(callable $num): self {
        return new self(
            fn() => [0, 0],
            /**
             * @param int[] $result
             */
            function(array &$result, mixed $arg) use($num): void { $result[0] += $num($arg); $result[1]++; },
            /**
             * @param  int[] $result
             * @return float
             */
            fn(array $result) => $result[0] / $result[1]
        );
    }

    /**
     * restarts collection with a fresh instance
     *
     * @return Collector
     */
    public function fork(): self
    {
        return new self($this->supplier, $this->accumulator, $this->finisher);
    }

    /**
     * adds given element and key to result structure
     */
    public function accumulate(mixed $element, mixed $key): void
    {
        $accumulate = $this->accumulator;
        $accumulate($this->structure, $element, $key);
    }

    /**
     * finishes collection of result
     *
     * @return T finished result
     */
    public function finish()
    {
        if (null === $this->finisher) {
            return $this->structure;
        }

        $finish = $this->finisher;
        return $finish($this->structure);
    }
}
