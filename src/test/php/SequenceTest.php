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

use ArrayIterator;
use bovigo\callmap\NewInstance;
use IteratorAggregate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stubbles\sequence\assert\Provides;

use function bovigo\assert\{
    assertThat,
    assertNull,
    fail,
    predicate\equals,
    predicate\isOfSize
};
/**
 * Tests for stubbles\sequence\Sequence.
 *
 * @since 5.2.0
 */
class SequenceTest extends TestCase
{
    /**
     * Returns valid arguments for the `of()` method: Arrays, iterables,
     * iterators and generators.
     *
     * @return array<array<mixed>>
     */
    public static function validData(): array
    {
        $f = function() { yield 1; yield 2; yield 3; };
        return [
                [[1, 2, 3], 'array'],
                [new ArrayIterator([1, 2, 3]), '\Iterator'],
                [NewInstance::of(IteratorAggregate::class)
                        ->returns(['getIterator' => new ArrayIterator([1, 2, 3])]),
                 '\IteratorAggregate'
                ],
                [Sequence::of([1, 2, 3]), 'self'],
                [$f(), '\Generator']
        ];
    }

    /**
     * @param iterable<int> $iterable
     */
    #[Test]
    #[DataProvider('validData')]
    public function dataReturnsElementsAsArray(iterable $iterable, string $name): void
    {
        assertThat(Sequence::of($iterable), Provides::values([1, 2, 3]), $name);
    }

    /**
     * @since 8.1.0
     */
    #[Test]
    public function canCreateEmptySequenceByPassingNoParameters(): void
    {
        assertThat(Sequence::of(), Provides::data([]));
    }

    /**
     * @since 8.1.0
     */
    #[Test]
    public function canCreateSequenceFromOneNoniterableArgument(): void
    {
        assertThat(Sequence::of('foo'), Provides::data(['foo']));
    }

    /**
     * @since 8.1.0
     */
    #[Test]
    public function canCreateSequenceFromNiull(): void
    {
        assertThat(Sequence::of(null), Provides::data([null]));
    }

    #[Test]
    public function filterRemovesElements(): void
    {
        assertThat(
            Sequence::of(1, 2, 3, 4)
                ->filter(fn($e) => 0 === $e % 2),
            Provides::values([2, 4])
        );
    }

    #[Test]
    public function filterWithNativeFunction(): void
    {
        assertThat(
            Sequence::of(['Hello', 1337, 'World'])->filter('is_string'),
            Provides::values(['Hello', 'World'])
        );
    }

    #[Test]
    public function map(): void
    {
        assertThat(
            Sequence::of(1, 2, 3, 4)->map(fn($e) => $e * 2),
            Provides::values([2, 4, 6, 8])
        );
    }

    #[Test]
    public function mapWithNativeFunction(): void
    {
        assertThat(
            Sequence::of(1.9, 2.5, 3.1)->map('floor'),
            Provides::values([1.0, 2.0, 3.0])
        );
    }

    /**
     * @since 5.3.0
     */
    #[Test]
    public function mapKeys(): void
    {
        assertThat(
            Sequence::of(1, 2, 3, 4)
                ->mapKeys(fn($e) => $e * 2),
            Provides::data([0 => 1, 2 => 2, 4 => 3, 6 => 4])
        );
    }

    /**
     * @return  array<array<mixed>>
     */
    public static function countData(): array
    {
        return [
            [0, []],
            [1, [1]],
            [4, [1, 2, 3, 4]]
        ];
    }

    /**
     * @param int[] $elements
     */
    #[Test]
    #[DataProvider('countData')]
    public function sequenceIsCountable(int $expectedLength, array $elements): void
    {
        assertThat(Sequence::of($elements), isOfSize($expectedLength));
    }

    /**
     * @return  array<array<mixed>>
     */
    public static function sumData(): array
    {
        return [
            [0, []],
            [1, [1]],
            [10, [1, 2, 3, 4]]
        ];
    }

    /**
     * @param int[] $elements
     */
    #[Test]
    #[DataProvider('sumData')]
    public function sum(int $expectedResult, array $elements): void
    {
        assertThat(
            Sequence::of($elements)->reduce()->toSum(),
            equals($expectedResult)
        );
    }

    /**
     * @return array<array<mixed>>
     */
    public static function minData(): array
    {
        return [
            [null, []],
            [1, [1]],
            [2, [10, 2, 7]]
        ];
    }

    /**
     * @param int[] $elements
     */
    #[Test]
    #[DataProvider('minData')]
    public function min(?int $expectedResult, array $elements): void
    {
        assertThat(
            Sequence::of($elements)->reduce()->toMin(),
            equals($expectedResult)
        );
    }

    /**
     * @return array<array<mixed>>
     */
    public static function maxData(): array
    {
        return [
            [null, []],
            [1, [1]],
            [10, [2, 10, 7]]
        ];
    }

    /**
     * @param int[] $elements
     */
    #[Test]
    #[DataProvider('maxData')]
    public function max(?int $expectedResult, array $elements): void
    {
        assertThat(
            Sequence::of($elements)->reduce()->toMax(),
            equals($expectedResult)
        );
    }

    #[Test]
    public function reduceReturnsNullForEmptyInputWhenNoIdentityGiven(): void
    {
        assertNull(Sequence::of([])->reduce(
            function(mixed $a, mixed $b)
            {
                fail('Should not be called');
            }
        ));
    }

    #[Test]
    public function reduceReturnsIdentityForEmptyInput(): void
    {
        assertThat(
            Sequence::of([])->reduce(
                function(mixed $a, mixed $b)
                {
                    fail('Should not be called');
                },
                -1
            ),
            equals(-1)
        );
    }

    #[Test]
    public function reduceUsedForSumming(): void
    {
        assertThat(
            Sequence::of(1, 2, 3, 4)
                ->reduce(fn(int $a, int $b) => $a + $b, 0),
            equals(10)
        );
    }

    #[Test]
    public function reduceUsedForMaxWithNativeMaxFunction(): void
    {
        assertThat(Sequence::of(7, 1, 10, 3)->reduce('max'), equals(10));
    }

    #[Test]
    public function reduceUsedForConcatenation(): void
    {
        assertThat(
            Sequence::of(['Hello', ' ', 'World'])
                ->reduce(fn(string $a, string $b) => $a . $b, ''),
            equals('Hello World')
        );
    }

    #[Test]
    public function collectUsedForAveraging(): void
    {
        $result = Sequence::of(1, 2, 3, 4)->collect()->with(
            fn() => ['total' => 0, 'sum' => 0],
            function(&$result, $element) { $result['total']++; $result['sum'] += $element; }
        );
        assertThat($result['sum'] / $result['total'], equals(2.5));
    }

    #[Test]
    public function collectUsedForJoining(): void
    {
        $result = Sequence::of('a', 'b', 'c')->collect()->with(
            fn() => '',
            function(&$result, $arg) { $result .= ', '.$arg; },
            fn($result) => substr($result, 2)
        );
        assertThat($result, equals('a, b, c'));
    }

    #[Test]
    public function firstReturnsNullForEmptyInput(): void
    {
        assertNull(Sequence::of([])->first());
    }

    #[Test]
    public function firstReturnsFirstArrayElement(): void
    {
        assertThat(Sequence::of(1, 2, 3)->first(), equals(1));
    }

    #[Test]
    public function eachOnEmptyInput(): void
    {
        assertThat(
            Sequence::of([])
                ->each(function() { fail('Should not be called'); }),
            equals(0)
        );
    }

    /**
     * @return array<array<mixed>>
     */
    public static function eachWithDifferentAmount(): array
    {
        return [
            [4, function() { /* intentionally empty */ }],
            [2, function($e) { if ('b' === $e) { return false; }}]
        ];
    }

    #[Test]
    #[DataProvider('eachWithDifferentAmount')]
    public function eachReturnsAmountOfElementsForWhichCallableWasExecuted(
        int $expected,
        callable $callable
    ): void {
        assertThat(
            Sequence::of('a', 'b', 'c', 'd')->each($callable),
            equals($expected)
        );
    }

    #[Test]
    public function eachAppliesGivenCallableForAllElements(): void
    {
        $collect = [];
        Sequence::of('a', 'b', 'c', 'd')
            ->each(function($e) use(&$collect) { $collect[] = $e; });
        assertThat($collect, equals(['a', 'b', 'c', 'd']));
    }

    #[Test]
    public function eachStopsWhenCallableReturnsFalse(): void
    {
        $collect = [];
        Sequence::of('a', 'b', 'c', 'd')->each(
            function($e) use(&$collect)
            {
                $collect[] = $e; if ('b' === $e) { return false; }
            }
        );
        assertThat($collect, equals(['a', 'b']));
    }

    #[Test]
    public function peekWithVarExport(): void
    {
        ob_start();
        Sequence::of(1, 2, 3, 4)->peek('var_export')->reduce()->toSum();
        $bytes = ob_get_contents();
        ob_end_clean();
        assertThat($bytes, equals('1234'));
    }

    #[Test]
    public function peekWithVarExportAndKeys(): void
    {
        ob_start();
        Sequence::of(['a' => 0, 'b' => 1, 'c' => 2, 'd' => 3])
            ->peek('var_export', 'var_export')
            ->reduce()
            ->toSum();
        $bytes = ob_get_contents();
        ob_end_clean();
        assertThat($bytes, equals("0'a'1'b'2'c'3'd'"));
    }

    #[Test]
    public function limitStopsAtNthArrayElement(): void
    {
        assertThat(Sequence::of(1, 2, 3)->limit(2), Provides::values([1, 2]));
    }

    #[Test]
    public function limitStopsAtNthInfiniteElement(): void
    {
        assertThat(
            Sequence::infinite(1, fn($i) => ++$i)->limit(2),
            Provides::values([1, 2])
        );
    }

    #[Test]
    public function limitStopsAtNthGeneratorElement(): void
    {
        assertThat(
            Sequence::generate(
                1,
                fn($i) => $i + 1,
                fn($i) => $i < 10
                )
            ->limit(2),
            Provides::values([1, 2])
        );
    }

    #[Test]
    public function skipIgnoresNumberOfArrayElements(): void
    {
        assertThat(Sequence::of(4, 5, 6)->skip(2), Provides::values([6]));
    }

    #[Test]
    public function skipIgnoresNumberOfInfiniteElements(): void
    {
        assertThat(
            Sequence::infinite(1, fn($i) => ++$i)
                ->skip(2)
                ->limit(3),
            Provides::values([3, 4, 5])
        );
    }

    #[Test]
    public function skipIgnoresNumberOfGeneratorElements(): void
    {
        assertThat(
            Sequence::generate(
                1,
                fn($i) => $i + 1,
                fn($i) => $i < 10
            )->skip(2)
            ->limit(3),
            Provides::values([3, 4, 5])
        );
    }

    #[Test]
    public function appendCreatesNewCombinedSequenceWithGivenSequence(): void
    {
        assertThat(
            Sequence::of(1, 2)->append(Sequence::of(3, 4)),
            Provides::values([1, 2, 3, 4])
        );
    }

    /**
     * @since 5.4.0
     */
    #[Test]
    public function appendCreatesNewCombinedSequenceWithGivenArray(): void
    {
        assertThat(
            Sequence::of(1, 2)->append([3, 4]),
            Provides::values([1, 2, 3, 4])
        );
    }

    /**
     * @since 5.4.0
     */
    #[Test]
    public function appendCreatesNewCombinedSequenceWithGivenIterator(): void
    {
        assertThat(
            Sequence::of(1, 2)->append(new ArrayIterator([3, 4])),
            Provides::values([1, 2, 3, 4])
        );
    }

    /**
     * @since 8.0.0
     */
    #[Test]
    public function appendCreatesNewCombinedSequenceWithGivenIteratorAggregate(): void
    {
        $iteratorAggregate = NewInstance::of(IteratorAggregate::class)
            ->returns(['getIterator' => new ArrayIterator([3, 4])]);
        assertThat(
            Sequence::of(1, 2)->append($iteratorAggregate),
            Provides::values([1, 2, 3, 4])
        );
    }

    /**
     * @since 8.0.0
     */
    #[Test]
    public function appendCreatesNewCombinedSequenceFromInitialIteratorAggregate(): void
    {
        $iteratorAggregate = NewInstance::of(IteratorAggregate::class)
            ->returns(['getIterator' => new ArrayIterator([1, 2])]);
        assertThat(
            Sequence::of($iteratorAggregate)->append([3, 4]),
            Provides::values([1, 2, 3, 4])
        );
    }

    /**
     * @return array<array<mixed>>
     * @since  5.4.0
     */
    public static function initialSequence(): array
    {
        return [[[1, 2]], [new ArrayIterator([1, 2])]];
    }
    /**
     * @param iterable<int> $initial
     * @since 5.4.0
     */
    #[Test]
    #[DataProvider('initialSequence')]
    public function appendCreatesNewCombinedSequenceWithGivenElement(iterable $initial): void
    {
        assertThat(Sequence::of($initial)->append(3), Provides::values([1, 2, 3]));
    }

    #[Test]
    public function isUseableInsideForeach(): void
    {
        $result = [];
        foreach (Sequence::of(['foo' => 1, 'bar' => 2, 'baz' => 3]) as $key => $element) {
          $result[$key] = $element;
        }

        assertThat($result, equals(['foo' => 1, 'bar' => 2, 'baz' => 3]));
    }

    #[Test]
    public function dataReturnsCompleteDataAsArray(): void
    {
        assertThat(
            Sequence::of(new ArrayIterator(['foo' => 'bar', 'baz' => 303])),
            Provides::data(['foo' => 'bar', 'baz' => 303])
        );
    }

    /**
     * @since 5.3.2
     */
    #[Test]
    public function canBeSerializedToJson(): void
    {
        assertThat(
            json_encode(Sequence::of(['one' => 1, 2, 'three' => 3, 4])),
            equals('{"one":1,"0":2,"three":3,"1":4}')
        );
    }
}
