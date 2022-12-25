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
 * A reducer takes a sequence and reduces it to one value.
 *
 * @since 5.2.0
 */
class Reducer
{
    /**
     * constructor
     *
     * @internal create a reducer with $sequence->reduce() instead
     */
    public function __construct(private Sequence $sequence) { }

    /**
     * reduce with given accumulator
     */
    public function with(callable $accumulator, mixed $identity = null): mixed
    {
        return $this->sequence->reduce($accumulator, $identity);
    }

    /**
     * reduce to sum of all elements
     *
     * @api
     * @param callable $summer optional different summing function, i.e. when elements are not numbers
     */
    public function toSum(?callable $summer = null): int
    {
        if (null === $summer) {
            $summer = fn(int $sum, int $element) => $sum += $element;
        }

        return $this->with($summer, 0);
    }

    /**
     * reduce to smallest element
     *
     * @api
     * @param   callable  $min  optional  different function to calculate the minimum, i.e. when elements are not numbers
     * @return  mixed
     */
    public function toMin(?callable $min = null)
    {
        if (null === $min) {
            /**
             * null-aware implementation of min()
             * 
             * Can't use min() as $smallest is initially null but actual elements
             * were not checked yet, and min() considers null to always be the
             * minimum value.
             */
            $min = fn($smallest, $element) =>
                (null === $smallest || $element < $smallest) ? $element : $smallest;
        }

        return $this->with($min);
    }

    /**
     * reduce to largest element
     *
     * This is a terminal operation.
     *
     * @api
     * @param callable $max optional different function to calculate the maximum, i.e. when elements are not numbers
     */
    public function toMax(?callable $max = null)
    {
        return $this->with($max ?: 'max');
    }
}
