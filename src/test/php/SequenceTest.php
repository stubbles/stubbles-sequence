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
use PHPUnit\Framework\TestCase;
use stubbles\sequence\assert\Provides;

use function bovigo\assert\{
    assertThat,
    assertNull,
    expect,
    fail,
    predicate\equals,
    predicate\isOfSize
};
/**
 * Tests for stubbles\sequence\Sequence.
 *
 * @since  5.2.0
 */
class SequenceTest extends TestCase
{
    /**
     * Returns valid arguments for the `of()` method: Arrays, iterables,
     * iterators and generators.
     *
     * @return array<array<mixed>>
     */
    public function validData(): array
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
     * @test
     * @dataProvider validData
     */
    public function dataReturnsElementsAsArray(iterable $iterable, string $name): void
    {
        assertThat(Sequence::of($iterable), Provides::values([1, 2, 3]), $name);
    }

    /**
     * @test
     * @since 8.1.0
     */
    public function canCreateEmptySequenceByPassingNoParameters(): void
    {
        assertThat(Sequence::of(), Provides::data([]));
    }

    /**
     * @test
     * @since 8.1.0
     */
    public function canCreateSequenceFromOneNoniterableArgument(): void
    {
        assertThat(Sequence::of('foo'), Provides::data(['foo']));
    }

    /**
     * @test
     * @since 8.1.0
     */
    public function canCreateSequenceFromNiull(): void
    {
        assertThat(Sequence::of(null), Provides::data([null]));
    }

    /**
     * @test
     */
    public function filterRemovesElements(): void
    {
        assertThat(
            Sequence::of(1, 2, 3, 4)
                ->filter(fn($e) => 0 === $e % 2),
            Provides::values([2, 4])
        );
    }

    /**
     * @test
     */
    public function filterWithNativeFunction(): void
    {
        assertThat(
            Sequence::of(['Hello', 1337, 'World'])->filter('is_string'),
            Provides::values(['Hello', 'World'])
        );
    }

    /**
     * @test
     */
    public function map(): void
    {
        assertThat(
            Sequence::of(1, 2, 3, 4)->map(fn($e) => $e * 2),
            Provides::values([2, 4, 6, 8])
        );
    }

    /**
     * @test
     */
    public function mapWithNativeFunction(): void
    {
        assertThat(
            Sequence::of(1.9, 2.5, 3.1)->map('floor'),
            Provides::values([1.0, 2.0, 3.0])
        );
    }

    /**
     * @test
     * @since 5.3.0
     */
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
    public function countData(): array
    {
        return [
            [0, []],
            [1, [1]],
            [4, [1, 2, 3, 4]]
        ];
    }

    /**
     * @param int[] $elements
     * @test
     * @dataProvider countData
     */
    public function sequenceIsCountable(int $expectedLength, array $elements): void
    {
        assertThat(Sequence::of($elements), isOfSize($expectedLength));
    }

    /**
     * @return  array<array<mixed>>
     */
    public function sumData(): array
    {
        return [
            [0, []],
            [1, [1]],
            [10, [1, 2, 3, 4]]
        ];
    }

    /**
     * @param int[] $elements
     * @test
     * @dataProvider  sumData
     */
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
    public function minData(): array
    {
        return [
            [null, []],
            [1, [1]],
            [2, [10, 2, 7]]
        ];
    }

    /**
     * @param int[] $elements
     * @test
     * @dataProvider minData
     */
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
    public function maxData(): array
    {
        return [
            [null, []],
            [1, [1]],
            [10, [2, 10, 7]]
        ];
    }

    /**
     * @param int[] $elements
     * @test
     * @dataProvider maxData
     */
    public function max(?int $expectedResult, array $elements): void
    {
        assertThat(
            Sequence::of($elements)->reduce()->toMax(),
            equals($expectedResult)
        );
    }

    /**
     * @test
     */
    public function reduceReturnsNullForEmptyInputWhenNoIdentityGiven(): void
    {
        assertNull(Sequence::of([])->reduce(
            function(mixed $a, mixed $b)
            {
                fail('Should not be called');
            }
        ));
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function reduceUsedForSumming(): void
    {
        assertThat(
            Sequence::of(1, 2, 3, 4)
                ->reduce(fn(int $a, int $b) => $a + $b, 0),
            equals(10)
        );
    }

    /**
     * @test
     */
    public function reduceUsedForMaxWithNativeMaxFunction(): void
    {
        assertThat(Sequence::of(7, 1, 10, 3)->reduce('max'), equals(10));
    }

    /**
     * @test
     */
    public function reduceUsedForConcatenation(): void
    {
        assertThat(
            Sequence::of(['Hello', ' ', 'World'])
                ->reduce(fn(string $a, string $b) => $a . $b, ''),
            equals('Hello World')
        );
    }

    /**
     * @test
     */
    public function collectUsedForAveraging(): void
    {
        $result = Sequence::of(1, 2, 3, 4)->collect()->with(
            fn() => ['total' => 0, 'sum' => 0],
            function(&$result, $element) { $result['total']++; $result['sum'] += $element; }
        );
        assertThat($result['sum'] / $result['total'], equals(2.5));
    }

    /**
     * @test
     */
    public function collectUsedForJoining(): void
    {
        $result = Sequence::of('a', 'b', 'c')->collect()->with(
            fn() => '',
            function(&$result, $arg) { $result .= ', '.$arg; },
            fn($result) => substr($result, 2)
        );
        assertThat($result, equals('a, b, c'));
    }

    /**
     * @test
     */
    public function firstReturnsNullForEmptyInput(): void
    {
        assertNull(Sequence::of([])->first());
    }

    /**
     * @test
     */
    public function firstReturnsFirstArrayElement(): void
    {
        assertThat(Sequence::of(1, 2, 3)->first(), equals(1));
    }

    /**
     * @test
     */
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
    public function eachWithDifferentAmount(): array
    {
        return [
            [4, function() { /* intentionally empty */ }],
            [2, function($e) { if ('b' === $e) { return false; }}]
        ];
    }

    /**
     * @test
     * @dataProvider  eachWithDifferentAmount
     */
    public function eachReturnsAmountOfElementsForWhichCallableWasExecuted(
        int $expected,
        callable $callable
    ): void {
        assertThat(
            Sequence::of('a', 'b', 'c', 'd')->each($callable),
            equals($expected)
        );
    }

    /**
     * @test
     */
    public function eachAppliesGivenCallableForAllElements(): void
    {
        $collect = [];
        Sequence::of('a', 'b', 'c', 'd')
            ->each(function($e) use(&$collect) { $collect[] = $e; });
        assertThat($collect, equals(['a', 'b', 'c', 'd']));
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function peekWithVarExport(): void
    {
        ob_start();
        Sequence::of(1, 2, 3, 4)->peek('var_export')->reduce()->toSum();
        $bytes = ob_get_contents();
        ob_end_clean();
        assertThat($bytes, equals('1234'));
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function limitStopsAtNthArrayElement(): void
    {
        assertThat(Sequence::of(1, 2, 3)->limit(2), Provides::values([1, 2]));
    }

    /**
     * @test
     */
    public function limitStopsAtNthInfiniteElement(): void
    {
        assertThat(
            Sequence::infinite(1, fn($i) => ++$i)->limit(2),
            Provides::values([1, 2])
        );
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function skipIgnoresNumberOfArrayElements(): void
    {
        assertThat(Sequence::of(4, 5, 6)->skip(2), Provides::values([6]));
    }

    /**
     * @test
     */
    public function skipIgnoresNumberOfInfiniteElements(): void
    {
        assertThat(
            Sequence::infinite(1, fn($i) => ++$i)
                ->skip(2)
                ->limit(3),
            Provides::values([3, 4, 5])
        );
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function appendCreatesNewCombinedSequenceWithGivenSequence(): void
    {
        assertThat(
            Sequence::of(1, 2)->append(Sequence::of(3, 4)),
            Provides::values([1, 2, 3, 4])
        );
    }

    /**
     * @test
     * @since  5.4.0
     */
    public function appendCreatesNewCombinedSequenceWithGivenArray(): void
    {
        assertThat(
            Sequence::of(1, 2)->append([3, 4]),
            Provides::values([1, 2, 3, 4])
        );
    }

    /**
     * @test
     * @since  5.4.0
     */
    public function appendCreatesNewCombinedSequenceWithGivenIterator(): void
    {
        assertThat(
            Sequence::of(1, 2)->append(new ArrayIterator([3, 4])),
            Provides::values([1, 2, 3, 4])
        );
    }

    /**
     * @test
     * @since  8.0.0
     */
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
     * @test
     * @since  8.0.0
     */
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
    public function initialSequence(): array
    {
        return [[[1, 2]], [new ArrayIterator([1, 2])]];
    }
    /**
     * @param iterable<int> $initial
     * @test
     * @dataProvider initialSequence
     * @since 5.4.0
     */
    public function appendCreatesNewCombinedSequenceWithGivenElement(iterable $initial): void
    {
        assertThat(Sequence::of($initial)->append(3), Provides::values([1, 2, 3]));
    }

    /**
     * @test
     */
    public function isUseableInsideForeach(): void
    {
        $result = [];
        foreach (Sequence::of(['foo' => 1, 'bar' => 2, 'baz' => 3]) as $key => $element) {
          $result[$key] = $element;
        }

        assertThat($result, equals(['foo' => 1, 'bar' => 2, 'baz' => 3]));
    }

    /**
     * @test
     */
    public function dataReturnsCompleteDataAsArray(): void
    {
        assertThat(
            Sequence::of(new ArrayIterator(['foo' => 'bar', 'baz' => 303])),
            Provides::data(['foo' => 'bar', 'baz' => 303])
        );
    }

    /**
     * @test
     * @since 5.3.2
     */
    public function canBeSerializedToJson(): void
    {
        assertThat(
            json_encode(Sequence::of(['one' => 1, 2, 'three' => 3, 4])),
            equals('{"one":1,"0":2,"three":3,"1":4}')
        );
    }
}
