<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\sequence\iterator;
/**
 * Maps values and/or keys from an underlying iterator.
 *
 * @since 8.0.0
 */
interface SelfDescribing
{
    /**
     * returns description of this iterator
     */
    public function description(): string;
}
