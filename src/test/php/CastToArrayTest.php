<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\sequence;
use PHPUnit\Framework\TestCase;
use stubbles\test\sequence\AsArray;
use stubbles\test\sequence\ToArray;
use function bovigo\assert\assertThat;
use function bovigo\assert\predicate\equals;
/**
 * Tests for stubbles\sequence\castToArray().
 *
 * @since 5.4.0
 * @group cast_to_array
 */
class CastToArrayTest extends TestCase
{
    /**
     * @test
     */
    public function castToArrayOnTraversable(): void
    {
        assertThat(
            castToArray(new \ArrayIterator(['foo' => 'bar', 'baz' => 303])),
            equals(['foo' => 'bar', 'baz' => 303])
        );
    }

    /**
     * @test
     */
    public function castToArrayOnArray(): void
    {
        assertThat(
            castToArray(['foo' => 'bar', 'baz' => 303]),
            equals(['foo' => 'bar', 'baz' => 303])
        );
    }

    /**
     * @test
     */
    public function castToArrayOnObject(): void
    {
        $object = new class() {
            public string $foo = 'bar';
            protected bool $bar = true;
            private int $baz = 303;
        };
        assertThat(
            castToArray($object),
            equals(['foo' => 'bar', 'bar' => true, 'baz' => 303])
        );
    }

    /**
     * @test
     */
    public function castToArrayOnObjectWithAsArrayMethod(): void
    {
        assertThat(
            castToArray(new AsArray()),
            equals(['foo' => 'bar', 'baz' => 303])
        );
    }

    /**
     * @test
     */
    public function castToArrayOnObjectWithToArrayMethod(): void
    {
        assertThat(
            castToArray(new ToArray()),
            equals(['foo' => 'bar', 'baz' => 303])
        );
    }

    /**
     * @test
     */
    public function castToArrayOnScalarValue(): void
    {
        assertThat(castToArray(303), equals([303]));
    }
}
