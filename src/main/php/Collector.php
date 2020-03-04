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
 * A collector accumulates elements into a structure, optionally transforming the result into a final representation.
 *
 * @since  5.2.0
 * @template T
 */
class Collector
{
    /**
     * returns a fresh structure to collect elements into
     *
     * @var  callable
     */
    private $supplier;
    /**
     * structure to collect elements into
     *
     * @var  mixed
     */
    private $structure;
    /**
     * accumulates elements into structure
     *
     * @var  callable
     */
    private $accumulator;
    /**
     * final operation after all elements have been added to the structure
     *
     * @var  callable|null
     */
    private $finisher;

    /**
     * constructor
     *
     * @param callable $supplier    returns a fresh structure to collect elements into
     * @param callable $accumulator accumulates elements into structure
     * @param callable $finisher    optional final operation after all elements have been added to the structure
     */
    public function __construct(callable $supplier, callable $accumulator, callable $finisher = null)
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
     * @return \stubbles\sequence\Collector<array>
     */
    public static function forList(): self
    {
        return new self(
            function(): array { return []; },
            /**
             * @param  mixed[]  $list
             * @param  mixed    $element
             */
            function(array &$list, $element) { $list[] = $element; }
        );
    }

    /**
     * returns a collector for maps
     *
     * @api
     * @param  callable $keySelector   optional function to select the key for the map entry
     * @param  callable $valueSelector optional function to select the value for the map entry
     * @return \stubbles\sequence\Collector<array>
     */
    public static function forMap(callable $keySelector = null, callable $valueSelector = null): self
    {
        $selectKey   = (null !== $keySelector) ? $keySelector : function($value, $key) { return $key; };
        $selectValue = (null !== $valueSelector) ? $valueSelector : function($value, $key) { return $value; };
        return new self(
            function(): array { return []; },
            /**
             * @param array      $map
             * @param mixed      $element
             * @param int|string $key
             */
            function(array &$map, $element, $key) use($selectKey, $selectValue)
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
     * @return \stubbles\sequence\Collector<float>
     */
    public static function forSum(callable $num): self {
        return new self(
            function(): float { return 0; },
            /**
             * @param float $result
             * @param mixed $element
             */
            function(float &$result, $element) use($num) { $result+= $num($element); }
        );
    }

    /**
     * returns a collector to calculate an average for all the given elements
     *
     * @api
     * @param  callable $num callable which retrieves a number from a given element
     * @return \stubbles\sequence\Collector<float>
     */
    public static function forAverage(callable $num): self {
        return new self(
            function(): array { return [0, 0]; },
            /**
             * @param int[] $result
             * @param mixed $arg
             */
            function(array &$result, $arg) use($num) { $result[0] += $num($arg); $result[1]++; },
            /**
             * @param  int[] $result
             * @return float
             */
            function(array $result): float { return $result[0] / $result[1]; }
        );
    }

    /**
     * restarts collection with a fresh instance
     *
     * @return \stubbles\sequence\Collector
     */
    public function fork(): self
    {
        return new self($this->supplier, $this->accumulator, $this->finisher);
    }

    /**
     * adds given element and key to result structure
     *
     * @param mixed $element
     * @param mixed $key
     */
    public function accumulate($element, $key): void
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
