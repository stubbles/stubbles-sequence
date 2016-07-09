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
 *
 * @package  stubbles\sequence
 */
namespace stubbles\sequence;
use stubbles\sequence\assert\Provides;

use function bovigo\assert\assert;
use function bovigo\assert\assertNull;
use function bovigo\assert\expect;
use function bovigo\assert\predicate\equals;
use function bovigo\assert\predicate\isOfSize;
/**
 * Tests for stubbles\sequence\Sequence.
 *
 * @since  5.2.0
 */
class SequenceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function sequenceOfInvalidElementsThrowsIllegalArgumentException()
    {
        expect(function() {
                Sequence::of(new \stdClass());
        })
        ->throws(\InvalidArgumentException::class);
    }

    /**
     * Returns valid arguments for the `of()` method: Arrays, iterables,
     * iterators and generators.
     *
     * @return  array
     */
    public function validData(): array
    {
        $f = function() { yield 1; yield 2; yield 3; };
        return [
                [[1, 2, 3], 'array'],
                [new \ArrayIterator([1, 2, 3]), 'iterator'],
                [Sequence::of([1, 2, 3]), 'self'],
                [$f(), 'generator']
        ];
    }

    /**
     * @param  array   $input
     * @param  string  $name
     * @test
     * @dataProvider  validData
     */
    public function dataReturnsElementsAsArray($input, $name)
    {
        assert(Sequence::of($input), Provides::values([1, 2, 3]), $name);
    }

    /**
     * @test
     */
    public function filterRemovesElements()
    {
        assert(
                Sequence::of([1, 2, 3, 4])
                        ->filter(function($e) { return 0 === $e % 2; }),
                Provides::values([2, 4])
        );
    }

    /**
     * @test
     */
    public function filterWithNativeFunction()
    {
        assert(
                Sequence::of(['Hello', 1337, 'World'])->filter('is_string'),
                Provides::values(['Hello', 'World'])
        );
    }

    /**
     * @test
     */
    public function map()
    {
        assert(
                Sequence::of([1, 2, 3, 4])->map(function($e) { return $e * 2; }),
                Provides::values([2, 4, 6, 8])
        );
    }

    /**
     * @test
     */
    public function mapWithNativeFunction()
    {
        assert(
                Sequence::of([1.9, 2.5, 3.1])->map('floor'),
                Provides::values([1.0, 2.0, 3.0])
        );
    }

    /**
     * @test
     * @since  5.3.0
     */
    public function mapKeys()
    {
        assert(
                Sequence::of([1, 2, 3, 4])
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
        assert(Sequence::of($elements), isOfSize($expectedLength));
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
        assert(
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
        assert(
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
        assert(
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
        assert(
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
        assert(
                Sequence::of([1, 2, 3, 4])
                        ->reduce(function($a, $b) { return $a + $b; }),
                equals(10)
        );
    }

    /**
     * @test
     */
    public function reduceUsedForMaxWithNativeMaxFunction()
    {
        assert(Sequence::of([7, 1, 10, 3])->reduce('max'), equals(10));
    }

    /**
     * @test
     */
    public function reduceUsedForConcatenation()
    {
        assert(
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
        $result = Sequence::of([1, 2, 3, 4])->collect()->with(
                function() { return ['total' => 0, 'sum' => 0]; },
                function(&$result, $element) { $result['total']++; $result['sum'] += $element; }
        );
        assert($result['sum'] / $result['total'], equals(2.5));
    }

    /**
     * @test
     */
    public function collectUsedForJoining()
    {
        $result = Sequence::of(['a', 'b', 'c'])->collect()->with(
                function() { return ''; },
                function(&$result, $arg) { $result .= ', '.$arg; },
                function($result) { return substr($result, 2); }
        );
        assert($result, equals('a, b, c'));
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
        assert(Sequence::of([1, 2, 3])->first(), equals(1));
    }

    /**
     * @test
     */
    public function eachOnEmptyInput()
    {
        assert(
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
        assert(
                Sequence::of(['a', 'b', 'c', 'd'])->each($callable),
                equals($expected)
        );
    }

    /**
     * @test
     */
    public function eachAppliesGivenCallableForAllElements()
    {
        $collect = [];
        Sequence::of(['a', 'b', 'c', 'd'])
                ->each(function($e) use(&$collect) { $collect[] = $e; });
        assert($collect, equals(['a', 'b', 'c', 'd']));
    }

    /**
     * @test
     */
    public function eachStopsWhenCallableReturnsFalse()
    {
        $collect = [];
        Sequence::of(['a', 'b', 'c', 'd'])->each(
                function($e) use(&$collect)
                {
                    $collect[] = $e; if ('b' === $e) { return false; }
                }
        );
        assert($collect, equals(['a', 'b']));
    }

    /**
     * @test
     */
    public function peekWithVarExport()
    {
        ob_start();
        Sequence::of([1, 2, 3, 4])->peek('var_export')->reduce()->toSum();
        $bytes = ob_get_contents();
        ob_end_clean();
        assert($bytes, equals('1234'));
    }

    /**
     * @test
     */
    public function peekWithVarExportAndKeys()
    {
        ob_start();
        Sequence::of(['a', 'b', 'c', 'd'])
                ->peek('var_export', 'var_export')
                ->reduce()
                ->toSum();
        $bytes = ob_get_contents();
        ob_end_clean();
        assert($bytes, equals("'a'0'b'1'c'2'd'3"));
    }

    /**
     * @test
     */
    public function limitStopsAtNthArrayElement()
    {
        assert(Sequence::of([1, 2, 3])->limit(2), Provides::values([1, 2]));
    }

    /**
     * @test
     */
    public function limitStopsAtNthInfiniteElement()
    {
        assert(
                Sequence::infinite(1, function($i) { return ++$i; })->limit(2),
                Provides::values([1, 2])
        );
    }

    /**
     * @test
     */
    public function limitStopsAtNthGeneratorElement()
    {
        assert(
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
        assert(Sequence::of([4, 5, 6])->skip(2), Provides::values([6]));
    }

    /**
     * @test
     */
    public function skipIgnoresNumberOfInfiniteElements()
    {
        assert(
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
        assert(
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
        assert(
                Sequence::of([1, 2])->append(Sequence::of([3, 4])),
                Provides::values([1, 2, 3, 4])
        );
    }

    /**
     * @test
     * @since  5.4.0
     */
    public function appendCreatesNewCombinedSequenceWithGivenArray()
    {
        assert(
                Sequence::of([1, 2])->append([3, 4]),
                Provides::values([1, 2, 3, 4])
        );
    }

    /**
     * @test
     * @since  5.4.0
     */
    public function appendCreatesNewCombinedSequenceWithGivenIterator()
    {
        assert(
                Sequence::of([1, 2])->append(new \ArrayIterator([3, 4])),
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
        assert(Sequence::of($initial)->append(3), Provides::values([1, 2, 3]));
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

        assert($result, equals(['foo' => 1, 'bar' => 2, 'baz' => 3]));
    }

    /**
     * @test
     */
    public function dataReturnsCompleteDataAsArray()
    {
        assert(
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
        assert(
                json_encode(Sequence::of(['one' => 1, 2, 'three' => 3, 4])),
                equals('{"one":1,"0":2,"three":3,"1":4}')
        );
    }
}
