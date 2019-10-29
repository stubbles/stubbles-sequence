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
 * @since  5.4.0
 * @group  cast_to_array
 */
class CastToArrayTest extends TestCase
{
    /**
     * @test
     */
    public function castToArrayOnTraversable()
    {
        assertThat(
                castToArray(new \ArrayIterator(['foo' => 'bar', 'baz' => 303])),
                equals(['foo' => 'bar', 'baz' => 303])
        );
    }

    /**
     * @test
     */
    public function castToArrayOnArray()
    {
        assertThat(
                castToArray(['foo' => 'bar', 'baz' => 303]),
                equals(['foo' => 'bar', 'baz' => 303])
        );
    }

    /**
     * @test
     */
    public function castToArrayOnObject()
    {
        $object = new \stdClass();
        $object->foo = 'bar';
        $object->baz = 303;
        assertThat(castToArray($object), equals(['foo' => 'bar', 'baz' => 303]));
    }

    /**
     * @test
     */
    public function castToArrayOnObjectWithAsArrayMethod()
    {
        assertThat(
                castToArray(new AsArray()),
                equals(['foo' => 'bar', 'baz' => 303])
        );
    }

    /**
     * @test
     */
    public function castToArrayOnObjectWithToArrayMethod()
    {
        assertThat(
                castToArray(new ToArray()),
                equals(['foo' => 'bar', 'baz' => 303])
        );
    }

    /**
     * @test
     */
    public function castToArrayOnScalarValue()
    {
        assertThat(castToArray(303), equals([303]));
    }
}
