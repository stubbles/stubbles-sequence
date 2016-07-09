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
namespace stubbles\sequence;
use function bovigo\assert\assert;
use function bovigo\assert\predicate\equals;
use function bovigo\assert\predicate\isInstanceOf;
use function bovigo\assert\predicate\isSameAs;
/**
 * Tests for stubbles\lang\*().
 *
 * @since  3.1.0
 * @group  ensure_callable
 */
class FunctionsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @since  4.0.0
     */
    public function ensureCallableDoesNotChangeClosures()
    {
        $closure = function() { return true; };
        assert(ensureCallable($closure), isSameAs($closure));
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function ensureCallableDoesNotChangeCallbackWithInstance()
    {
        $callback = [$this, __FUNCTION__];
        assert(ensureCallable($callback), isSameAs($callback));
    }

    /**
     * helper method for test
     */
    public static function example()
    {
        // intentionally empty
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function ensureCallableDoesNotChangeCallbackWithStaticMethod()
    {
        $callback = [__CLASS__, 'example'];
        assert(ensureCallable($callback), isSameAs($callback));
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function ensureCallableDoesNotWrapUserlandFunction()
    {
        assert(
                ensureCallable('stubbles\sequence\ensureCallable'),
                isSameAs('stubbles\sequence\ensureCallable')
        );
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function ensureCallableWrapsInternalFunction()
    {
        assert(ensureCallable('strlen'), isInstanceOf(\Closure::class));
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function ensureCallableAlwaysReturnsSameClosureForSameFunction()
    {
        assert(ensureCallable('strlen'), isSameAs(ensureCallable('strlen')));
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function ensureCallableReturnsClosureThatPassesArgumentsAndReturnsValue()
    {
        $strlen = ensureCallable('strlen');
        assert($strlen('foo'), equals(3));
    }
}
