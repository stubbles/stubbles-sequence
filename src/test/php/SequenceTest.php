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
use bovigo\callmap\NewInstance;
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
     */
    public function validData(): array
    {
        $f = function() { yield 1; yield 2; yield 3; };
        return [
                [[1, 2, 3], 'array'],
                [new \ArrayIterator([1, 2, 3]), '\Iterator'],
                [NewInstance::of(\IteratorAggregate::class)
                        ->returns(['getIterator' => new \ArrayIterator([1, 2, 3])]),
                 '\IteratorAggregate'
                ],
                [Sequence::of([1, 2, 3]), 'self'],
                [$f(), '\Generator']
        ];
    }

    /**
     * @test
     * @dataProvider  validData
     */
    public function dataReturnsElementsAsArray($iterable, string $name)
    {
        assertThat(Sequence::of($iterable), Provides::values([1, 2, 3]), $name);
    }

    /**
     * @test
     * @since  8.1.0
     */
    public function canCreateEmptySequenceByPassingNoParameters()
    {
        assertThat(Sequence::of(), Provides::data([]));
    }

    /**
     * @test
     * @since  8.1.0
     */
    public function canCreateSequenceFromOneNoniterableArgument()
    {
        assertThat(Sequence::of('foo'), Provides::data(['foo']));
    }

    /**
     * @test
     * @since  8.1.0
     */
    public function canCreateSequenceFromNiull()
    {
        assertThat(Sequence::of(null), Provides::data([null]));
    }

    /**
     * @test
     */
    public function filterRemovesElements()
    {
        assertThat(
                Sequence::of(1, 2, 3, 4)
                        ->filter(function($e) { return 0 === $e % 2; }),
                Provides::values([2, 4])
        );
    }

    /**
     * @test
     */
    public function filterWithNativeFunction()
    {
        assertThat(
                Sequence::of(['Hello', 1337, 'World'])->filter('is_string'),
                Provides::values(['Hello', 'World'])
        );
    }

    /**
     * @test
     */
    public function map()
    {
        assertThat(
                Sequence::of(1, 2, 3, 4)->map(function($e) { return $e * 2; }),
                Provides::values([2, 4, 6, 8])
        );
    }

    /**
     * @test
     */
    public function mapWithNativeFunction()
    {
        assertThat(
                Sequence::of(1.9, 2.5, 3.1)->map('floor'),
                Provides::values([1.0, 2.0, 3.0])
        );
    }

    /**
     * @test
     * @since  5.3.0
     */
    public function mapKeys()
    {
        assertThat(
                Sequence::of(1, 2, 3, 4)
                        ->mapKeys(function($e) { return $e * 2; }),
                Provides::data([0 => 1, 2 => 2, 4 => 3, 6 => 4])
        );
    }

    /**
     * @return  array
     */
    public function countData()
    {
        return [
            [0, []],
            [1, [1]],
            [4, [1, 2, 3, 4]]
        ];
    }

    /**
     * @param  int    $expectedLength
     * @param  array  $elements
     * @test
     * @dataProvider  countData
     */
    public function sequenceIsCountable($expectedLength, $elements)
    {
        assertThat(Sequence::of($elements), isOfSize($expectedLength));
    }

    /**
     * @return  array
     */
    public function sumData()
    {
        return [
            [0, []],
            [1, [1]],
            [10, [1, 2, 3, 4]]
        ];
    }

    /**
     * @param  int    $expectedResult
     * @param  array  $elements
     * @test
     * @dataProvider  sumData
     */
    public function sum($expectedResult, $elements)
    {
        assertThat(
                Sequence::of($elements)->reduce()->toSum(),
                equals($expectedResult)
        );
    }

    /**
     * @return  array
     */
    public function minData()
    {
        return [
            [null, []],
            [1, [1]],
            [2, [10, 2, 7]]
        ];
    }

    /**
     * @param  int    $expectedResult
     * @param  array  $elements
     * @test
     * @dataProvider  minData
     */
    public function min($expectedResult, $elements)
    {
        assertThat(
                Sequence::of($elements)->reduce()->toMin(),
                equals($expectedResult)
        );
    }

    /**
     * @return  array
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
     * @param  int    $expectedResult
     * @param  array  $elements
     * @test
     * @dataProvider  maxData
     */
    public function max($expectedResult, $elements)
    {
        assertThat(
                Sequence::of($elements)->reduce()->toMax(),
                equals($expectedResult)
        );
    }

    /**
     * @test
     */
    public function reduceReturnsNullForEmptyInputWhenNoIdentityGiven()
    {
        assertNull(Sequence::of([])->reduce(
                function($a, $b)
                {
                    fail('Should not be called');
                }
        ));
    }

    /**
     * @test
     */
    public function reduceReturnsIdentityForEmptyInput()
    {
        assertThat(
                Sequence::of([])->reduce(
                        function($a, $b)
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
    public function reduceUsedForSumming()
    {
        assertThat(
                Sequence::of(1, 2, 3, 4)
                        ->reduce(function($a, $b) { return $a + $b; }),
                equals(10)
        );
    }

    /**
     * @test
     */
    public function reduceUsedForMaxWithNativeMaxFunction()
    {
        assertThat(Sequence::of(7, 1, 10, 3)->reduce('max'), equals(10));
    }

    /**
     * @test
     */
    public function reduceUsedForConcatenation()
    {
        assertThat(
                Sequence::of(['Hello', ' ', 'World'])
                        ->reduce(function($a, $b) { return $a . $b; }),
                equals('Hello World')
        );
    }

    /**
     * @test
     */
    public function collectUsedForAveraging()
    {
        $result = Sequence::of(1, 2, 3, 4)->collect()->with(
                function() { return ['total' => 0, 'sum' => 0]; },
                function(&$result, $element) { $result['total']++; $result['sum'] += $element; }
        );
        assertThat($result['sum'] / $result['total'], equals(2.5));
    }

    /**
     * @test
     */
    public function collectUsedForJoining()
    {
        $result = Sequence::of('a', 'b', 'c')->collect()->with(
                function() { return ''; },
                function(&$result, $arg) { $result .= ', '.$arg; },
                function($result) { return substr($result, 2); }
        );
        assertThat($result, equals('a, b, c'));
    }

    /**
     * @test
     */
    public function firstReturnsNullForEmptyInput()
    {
        assertNull(Sequence::of([])->first());
    }

    /**
     * @test
     */
    public function firstReturnsFirstArrayElement()
    {
        assertThat(Sequence::of(1, 2, 3)->first(), equals(1));
    }

    /**
     * @test
     */
    public function eachOnEmptyInput()
    {
        assertThat(
                Sequence::of([])
                        ->each(function() { fail('Should not be called'); }),
                equals(0)
        );
    }

    /**
     * @return  array
     */
    public function eachWithDifferentAmount()
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
    public function eachReturnsAmountOfElementsForWhichCallableWasExecuted($expected, $callable)
    {
        assertThat(
                Sequence::of('a', 'b', 'c', 'd')->each($callable),
                equals($expected)
        );
    }

    /**
     * @test
     */
    public function eachAppliesGivenCallableForAllElements()
    {
        $collect = [];
        Sequence::of('a', 'b', 'c', 'd')
                ->each(function($e) use(&$collect) { $collect[] = $e; });
        assertThat($collect, equals(['a', 'b', 'c', 'd']));
    }

    /**
     * @test
     */
    public function eachStopsWhenCallableReturnsFalse()
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
    public function peekWithVarExport()
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
    public function peekWithVarExportAndKeys()
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
    public function limitStopsAtNthArrayElement()
    {
        assertThat(Sequence::of(1, 2, 3)->limit(2), Provides::values([1, 2]));
    }

    /**
     * @test
     */
    public function limitStopsAtNthInfiniteElement()
    {
        assertThat(
                Sequence::infinite(1, function($i) { return ++$i; })->limit(2),
                Provides::values([1, 2])
        );
    }

    /**
     * @test
     */
    public function limitStopsAtNthGeneratorElement()
    {
        assertThat(
                Sequence::generate(
                        1,
                        function($i) { return $i + 1; },
                        function($i) { return $i < 10; }
                        )->limit(2),
                Provides::values([1, 2])
        );
    }

    /**
     * @test
     */
    public function skipIgnoresNumberOfArrayElements()
    {
        assertThat(Sequence::of(4, 5, 6)->skip(2), Provides::values([6]));
    }

    /**
     * @test
     */
    public function skipIgnoresNumberOfInfiniteElements()
    {
        assertThat(
                Sequence::infinite(1, function($i) { return ++$i; })
                        ->skip(2)
                        ->limit(3),
                Provides::values([3, 4, 5])
        );
    }

    /**
     * @test
     */
    public function skipIgnoresNumberOfGeneratorElements()
    {
        assertThat(
                Sequence::generate(
                        1,
                        function($i) { return $i + 1; },
                        function($i) { return $i < 10; }
                        )->skip(2)
                        ->limit(3),
                Provides::values([3, 4, 5])
        );
    }

    /**
     * @test
     */
    public function appendCreatesNewCombinedSequenceWithGivenSequence()
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
    public function appendCreatesNewCombinedSequenceWithGivenArray()
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
    public function appendCreatesNewCombinedSequenceWithGivenIterator()
    {
        assertThat(
                Sequence::of(1, 2)->append(new \ArrayIterator([3, 4])),
                Provides::values([1, 2, 3, 4])
        );
    }

    /**
     * @test
     * @since  8.0.0
     */
    public function appendCreatesNewCombinedSequenceWithGivenIteratorAggregate()
    {
        $iteratorAggregate = NewInstance::of(\IteratorAggregate::class)
                ->returns(['getIterator' => new \ArrayIterator([3, 4])]);
        assertThat(
                Sequence::of(1, 2)->append($iteratorAggregate),
                Provides::values([1, 2, 3, 4])
        );
    }

    /**
     * @test
     * @since  8.0.0
     */
    public function appendCreatesNewCombinedSequenceFromInitialIteratorAggregate()
    {
        $iteratorAggregate = NewInstance::of(\IteratorAggregate::class)
                ->returns(['getIterator' => new \ArrayIterator([1, 2])]);
        assertThat(
                Sequence::of($iteratorAggregate)->append([3, 4]),
                Provides::values([1, 2, 3, 4])
        );
    }

    /**
     * @return  array
     * @since   5.4.0
     */
    public function initialSequence()
    {
        return [[[1, 2]], [new \ArrayIterator([1, 2])]];
    }
    /**
     * @param  iterable  $initial
     * @test
     * @dataProvider  initialSequence
     * @since  5.4.0
     */
    public function appendCreatesNewCombinedSequenceWithGivenElement($initial)
    {
        assertThat(Sequence::of($initial)->append(3), Provides::values([1, 2, 3]));
    }

    /**
     * @test
     */
    public function isUseableInsideForeach()
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
    public function dataReturnsCompleteDataAsArray()
    {
        assertThat(
                Sequence::of(new \ArrayIterator(['foo' => 'bar', 'baz' => 303])),
                Provides::data(['foo' => 'bar', 'baz' => 303])
        );
    }

    /**
     * @test
     * @since  5.3.2
     */
    public function canBeSerializedToJson()
    {
        assertThat(
                json_encode(Sequence::of(['one' => 1, 2, 'three' => 3, 4])),
                equals('{"one":1,"0":2,"three":3,"1":4}')
        );
    }
}
