<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\sequence\iterator;

use ArrayIterator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use function bovigo\assert\assertThat;
use function bovigo\assert\assertNull;
use function bovigo\assert\expect;
use function bovigo\assert\fail;
use function bovigo\assert\predicate\equals;
/**
 * Tests for stubbles\sequence\iterator\MappingIterator.
 *
 * @since 5.0.0
 */
#[Group('iterator')]
class MappingIteratorTest extends TestCase
{
    /**
     * @since 5.3.0
     */
    #[Test]
    public function throwsInvalidArgumentExceptionWhenBothValueMapperAndKeyMapperAreNull(): void
    {
        expect(fn() =>
            new MappingIterator(new ArrayIterator(['foo', 'bar', 'baz']))
        )
            ->throws(InvalidArgumentException::class);
    }

    #[Test]
    public function mapsValueOnIteration(): void
    {
        $mapping = new MappingIterator(
            new ArrayIterator(['foo']),
            fn() => 'bar'
        );
        foreach ($mapping as $value) {
            assertThat($value, equals('bar'));
        }
    }

    #[Test]
    public function valueMapperCanOptionallyReceiveKey(): void
    {
        $mapping = new MappingIterator(
            new ArrayIterator(['foo' => 'bar']),
            fn($value, $key) => $key
        );
        foreach ($mapping as $value) {
            assertThat($value, equals('foo'));
        }
    }

    /**
     * @since 5.3.0
     */
    #[Test]
    public function keyMapperCanOptionallyReceiveValue(): void
    {
        $mapping = new MappingIterator(
            new ArrayIterator(['foo' => 'bar']),
            null,
            fn($key, $value) => $value
        );
        foreach ($mapping as $key => $value) {
            assertThat($key, equals('bar'));
        }
    }

    #[Test]
    public function valueMapperReceivesUnmappedKey(): void
    {
        $mapping = new MappingIterator(
            new ArrayIterator(['foo' => 'bar']),
            function($value, $key) { assertThat($key, equals('foo')); return 'mappedValue'; },
            fn() => 'mappedKey'
        );
        foreach ($mapping as $key => $value) {
            // intentionally empty
        }
    }

    /**
     * @since 5.3.0
     */
    #[Test]
    public function keyMapperReceivesUnmappedValue(): void
    {
        $mapping = new MappingIterator(
            new ArrayIterator(['foo' => 'bar']),
            fn() => 'mappedValue',
            function($key, $value) { assertThat($value, equals('bar')); return 'mappedKey'; }
        );
        foreach ($mapping as $key => $value) {
            // intentionally empty
        }
    }

    /**
     * @since 5.3.0
     */
    #[Test]
    public function doesNotMapValueWhenNoValueMapperProvided(): void
    {
        $mapping = new MappingIterator(
            new ArrayIterator(['foo' => 303, 'bar' => 808, 'baz' => '909']),
            null,
            fn() => 'mappedValue'
        );
        $values = [];
        foreach ($mapping as $key => $value) {
            $values[] = $value;
        }

        assertThat($values, equals([303, 808, '909']));
    }

    #[Test]
    public function doesNotMapKeyWhenNoKeyMapperProvided(): void
    {
        $mapping = new MappingIterator(
            new ArrayIterator(['foo' => 303, 'bar' => 808, 'baz' => '909']),
            fn() => 'mappedValue'
        );
        $keys = [];
        foreach ($mapping as $key => $value) {
            $keys[] = $key;
        }

        assertThat($keys, equals(['foo', 'bar', 'baz']));
    }

    #[Test]
    public function mapsKeyWhenKeyMapperProvided(): void
    {
        $mapping = new MappingIterator(
            new ArrayIterator(['foo' => 303, 'bar' => 808, 'baz' => 909]),
            fn() => 'mappedValue',
            fn() => 'mappedKey'
        );
        $keys = [];
        foreach ($mapping as $key => $value) {
            $keys[] = $key;
        }

        assertThat($keys, equals(['mappedKey', 'mappedKey', 'mappedKey']));
    }

    /**
     * @since 7.0.0
     */
    #[Test]
    public function doesNotCallValueMapperWhenEndOfIteratorReached(): void
    {
        $mapping = new MappingIterator(
            new ArrayIterator(['foo' => 303]),
            function() { fail('Should never be called'); }
        );
        $mapping->next();
        assertNull($mapping->current());
    }

    /**
     * @since 7.0.0
     */
    #[Test]
    public function doesNotCallKeyMapperWhenEndOfIteratorReached(): void
    {
        $mapping = new MappingIterator(
            new ArrayIterator(['foo' => 303]),
            function() { fail('Value mapper never be called'); },
            function() { fail('Key mapper never be called'); }
        );
        $mapping->next();
        assertNull($mapping->key());
    }
}
