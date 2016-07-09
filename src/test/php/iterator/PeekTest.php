<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\sequence
 */
namespace stubbles\sequence\iterator;
use function bovigo\assert\assert;
use function bovigo\assert\predicate\equals;
/**
 * Tests for stubbles\sequence\iterator\Peek.
 *
 * @group  iterator
 * @since  5.2.0
 */
class PeekTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function peekCallsValueConsumerWithCurrentValueOnIteration()
    {
        $result = '';
        $peek = new Peek(
                new \ArrayIterator(['foo', 'bar', 'baz']),
                function($value) use(&$result) { $result = $result . $value; }
        );
        foreach ($peek as $value) {
            // do nothing
        }

        assert($result, equals('foobarbaz'));
    }

    /**
     * @test
     */
    public function peekCallsKeyConsumerWithCurrentKeyOnIteration()
    {
        $result = '';
        $peek = new Peek(
                new \ArrayIterator(['foo' => 303, 'bar' => 404, 'baz' => 505]),
                function() { },
                function($key) use(&$result) { $result = $result . $key; }
        );
        foreach ($peek as $key => $value) {
            // do nothing
        }

        assert($result, equals('foobarbaz'));
    }

    /**
     * @test
     */
    public function keyConsumerIsNotCalledWhenNoKeyInForeachRequested()
    {
        $i = 0;
        $peek = new Peek(
                new \ArrayIterator(['foo' => 303, 'bar' => 404, 'baz' => 505]),
                function() { },
                function() { fail('Key consumer is not expected to be called'); }
        );
        foreach ($peek as $value) {
            $i++;
        }

        assert($i, equals(3));
    }
}
