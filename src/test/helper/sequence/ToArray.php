<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\test\sequence;
/**
 * Helper class for the test.
 */
class ToArray
{
    /**
     * @return  array<string,scalar>
     */
    public function toArray(): array
    {
        return ['foo' => 'bar', 'baz' => 303];
    }
}