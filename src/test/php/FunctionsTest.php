<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\sequence;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use function bovigo\assert\assertThat;
use function bovigo\assert\predicate\equals;
use function bovigo\assert\predicate\isInstanceOf;
use function bovigo\assert\predicate\isSameAs;
/**
 * Tests for stubbles\lang\*().
 *
 * @since 3.1.0
 */
#[Group('ensure_callable')]
class FunctionsTest extends TestCase
{
    /**
     * @since 4.0.0
     */
    #[Test]
    public function ensureCallableDoesNotChangeClosures(): void
    {
        $closure = function() { return true; };
        assertThat(ensureCallable($closure), isSameAs($closure));
    }

    /**
     * @since 4.0.0
     */
    #[Test]
    public function ensureCallableDoesNotChangeCallbackWithInstance(): void
    {
        $callback = [$this, __FUNCTION__];
        assertThat(ensureCallable($callback), isSameAs($callback));
    }

    /**
     * helper method for test
     */
    public static function example(): void
    {
        // intentionally empty
    }

    /**
     * @since 4.0.0
     */
    #[Test]
    public function ensureCallableDoesNotChangeCallbackWithStaticMethod(): void
    {
        $callback = [__CLASS__, 'example'];
        assertThat(ensureCallable($callback), isSameAs($callback));
    }

    /**
     * @since 4.0.0
     */
    #[Test]
    public function ensureCallableDoesNotWrapUserlandFunction(): void
    {
        assertThat(
            ensureCallable('stubbles\sequence\ensureCallable'),
            isSameAs('stubbles\sequence\ensureCallable')
        );
    }

    /**
     * @since 4.0.0
     */
    #[Test]
    public function ensureCallableWrapsInternalFunction(): void
    {
        assertThat(ensureCallable('strlen'), isInstanceOf(\Closure::class));
    }

    /**
     * @since 4.0.0
     */
    #[Test]
    public function ensureCallableAlwaysReturnsSameClosureForSameFunction(): void
    {
        assertThat(ensureCallable('strlen'), isSameAs(ensureCallable('strlen')));
    }

    /**
     * @since 4.0.0
     */
    #[Test]
    public function ensureCallableReturnsClosureThatPassesArgumentsAndReturnsValue(): void
    {
        $strlen = ensureCallable('strlen');
        assertThat($strlen('foo'), equals(3));
    }
}
