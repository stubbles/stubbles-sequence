<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\sequence\iterator;
use PHPUnit\Framework\TestCase;
use function bovigo\assert\assertThat;
use function bovigo\assert\assertNull;
use function bovigo\assert\expect;
use function bovigo\assert\fail;
use function bovigo\assert\predicate\equals;
/**
 * Tests for stubbles\sequence\iterator\MappingIterator.
 *
 * @since  5.0.0
 * @group  iterator
 */
class MappingIteratorTest extends TestCase
{
    /**
     * @test
     * @since  5.3.0
     */
    public function throwsInvalidArgumentExceptionWhenBothValueMapperAndKeyMapperAreNull(): void
    {
        expect(function() {
                new MappingIterator(new \ArrayIterator(['foo', 'bar', 'baz']));
        })
        ->throws(\InvalidArgumentException::class);
    }

    /**
     * @test
     */
    public function mapsValueOnIteration(): void
    {
        $mapping = new MappingIterator(
                new \ArrayIterator(['foo']),
                function($value) { return 'bar'; }
        );
        foreach ($mapping as $value) {
            assertThat($value, equals('bar'));
        }
    }

    /**
     * @test
     */
    public function valueMapperCanOptionallyReceiveKey(): void
    {
        $mapping = new MappingIterator(
                new \ArrayIterator(['foo' => 'bar']),
                function($value, $key) { return $key; }
        );
        foreach ($mapping as $value) {
            assertThat($value, equals('foo'));
        }
    }

    /**
     * @test
     * @since  5.3.0
     */
    public function keyMapperCanOptionallyReceiveValue(): void
    {
        $mapping = new MappingIterator(
                new \ArrayIterator(['foo' => 'bar']),
                null,
                function($key, $value) { return $value; }
        );
        foreach ($mapping as $key => $value) {
            assertThat($key, equals('bar'));
        }
    }

    /**
     * @test
     */
    public function valueMapperReceivesUnmappedKey(): void
    {
        $mapping = new MappingIterator(
                new \ArrayIterator(['foo' => 'bar']),
                function($value, $key) { assertThat($key, equals('foo')); return 'mappedValue'; },
                function($key) { return 'mappedKey'; }
        );
        foreach ($mapping as $key => $value) {
            // intentionally empty
        }
    }

    /**
     * @test
     * @since  5.3.0
     */
    public function keyMapperReceivesUnmappedValue(): void
    {
        $mapping = new MappingIterator(
                new \ArrayIterator(['foo' => 'bar']),
                function($value) { return 'mappedValue'; },
                function($key, $value) { assertThat($value, equals('bar')); return 'mappedKey'; }
        );
        foreach ($mapping as $key => $value) {
            // intentionally empty
        }
    }

    /**
     * @test
     * @since  5.3.0
     */
    public function doesNotMapValueWhenNoValueMapperProvided(): void
    {
        $mapping = new MappingIterator(
                new \ArrayIterator(['foo' => 303, 'bar' => 808, 'baz' => '909']),
                null,
                function($value) { return 'mappedValue'; }
        );
        $values = [];
        foreach ($mapping as $key => $value) {
            $values[] = $value;
        }

        assertThat($values, equals([303, 808, '909']));
    }

    /**
     * @test
     */
    public function doesNotMapKeyWhenNoKeyMapperProvided(): void
    {
        $mapping = new MappingIterator(
                new \ArrayIterator(['foo' => 303, 'bar' => 808, 'baz' => '909']),
                function($value) { return 'mappedValue'; }
        );
        $keys = [];
        foreach ($mapping as $key => $value) {
            $keys[] = $key;
        }

        assertThat($keys, equals(['foo', 'bar', 'baz']));
    }

    /**
     * @test
     */
    public function mapsKeyWhenKeyMapperProvided(): void
    {
        $mapping = new MappingIterator(
                new \ArrayIterator(['foo' => 303, 'bar' => 808, 'baz' => 909]),
                function($value) { return 'mappedValue'; },
                function($key) { return 'mappedKey'; }
        );
        $keys = [];
        foreach ($mapping as $key => $value) {
            $keys[] = $key;
        }

        assertThat($keys, equals(['mappedKey', 'mappedKey', 'mappedKey']));
    }

    /**
     * @test
     * @since  7.0.0
     */
    public function doesNotCallValueMapperWhenEndOfIteratorReached(): void
    {
        $mapping = new MappingIterator(
                new \ArrayIterator(['foo' => 303]),
                function() { fail('Should never be called'); }
        );
        $mapping->next();
        assertNull($mapping->current());
    }

    /**
     * @test
     * @since  7.0.0
     */
    public function doesNotCallKeyMapperWhenEndOfIteratorReached(): void
    {
        $mapping = new MappingIterator(
                new \ArrayIterator(['foo' => 303]),
                function() { fail('Value mapper never be called'); },
                function() { fail('Key mapper never be called'); }
        );
        $mapping->next();
        assertNull($mapping->key());
    }
}
