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
use PHPUnit\Framework\TestCase;
use stubbles\sequence\iterator\Limit;

use function bovigo\assert\assertThat;
use function bovigo\assert\predicate\equals;
/**
 * Tests for stubbles\sequence\Sequence->__toString().
 *
 * @since  8.0.0
 */
class SequenceToStringTest extends TestCase
{
    public function sequenceSourceTypes(): array
    {
        $f = function() { yield 1; yield 2; yield 3; };
        return [
                [[1, 2, 3], 'of array'],
                [new \ArrayIterator([1, 2, 3]), 'from ArrayIterator'],
                [Sequence::of([1, 2, 3]), 'of array'],
                [$f(), 'from Generator']
        ];
    }

    /**
     * @param  array   $input
     * @param  string  $name
     * @test
     * @dataProvider  sequenceSourceTypes
     */
    public function containsSourceType($input, $expectedSourceType)
    {
        assertThat(
                (string) Sequence::of($input),
                equals(Sequence::class . ' ' . $expectedSourceType)
        );
    }

    /**
     * @test
     */
    public function containsReferenceToFilterLambdaFunction()
    {
        assertThat(
                (string) Sequence::of(1, 2, 3, 4)
                        ->filter(function($e) { return 0 === $e % 2; }),
                equals(Sequence::class . ' of array filtered by a lambda function')
        );
    }

    /**
     * @test
     */
    public function containsReferenceToFilterNamedFunction()
    {
        assertThat(
                (string) Sequence::of('Hello', 1337, 'World')->filter('is_string'),
                equals(Sequence::class . ' of array filtered by is_string()')
        );
    }

    /**
     * @test
     */
    public function containsReferenceToMappingLambdaFunction()
    {
        assertThat(
                (string) Sequence::of([1, 2, 3, 4])->map(function($e) { return $e * 2; }),
                equals(Sequence::class . ' of array values mapped by a lambda function')
        );
    }

    /**
     * @test
     */
    public function containsReferenceToMappingNamedFunction()
    {
        assertThat(
                (string) Sequence::of([1.9, 2.5, 3.1])->map('floor'),
                equals(Sequence::class . ' of array values mapped by floor()')
        );
    }

    /**
     * @test
     */
    public function containsReferenceToKeyMappingFunction()
    {
        assertThat(
                (string) Sequence::of([1, 2, 3, 4])
                        ->mapKeys(function($e) { return $e * 2; }),
                equals(Sequence::class . ' of array keys mapped by a lambda function')
        );
    }

    /**
     * @test
     */
    public function containsNoReferenceToPeakFunction()
    {
        assertThat(
                (string) Sequence::of([1, 2, 3, 4])->peek('var_export'),
                equals(Sequence::class . ' of array')
        );
    }

    /**
     * @test
     */
    public function containsReferenceToLimit()
    {
        assertThat(
                (string)  Sequence::of([1, 2, 3])->limit(2),
                equals(Sequence::class . ' of array limited to 2 elements')
        );
    }

    /**
     * @test
     */
    public function containsReferenceToInfiniteGenerator()
    {
        assertThat(
                (string) Sequence::infinite(1, function($i) { return ++$i; })->limit(2),
                equals(
                        Sequence::class . ' starting at 1 continued by a lambda function'
                        . ' limited to 2 elements'
                )
        );
    }

    /**
     * @test
     */
    public function containsReferenceToGenerator()
    {
        assertThat(
                (string) Sequence::generate(
                        1,
                        function($i) { return $i + 1; },
                        function($i) { return $i < 10; }
                        )->limit(2),
                equals(
                        Sequence::class . ' starting at 1 continued by a lambda function'
                        . ' limited to 2 elements'
                )
        );
    }

    /**
     * @test
     */
    public function containsReferenceToSkippedElements()
    {
        assertThat(
                (string) Sequence::of(4, 5, 6)->skip(2),
                equals(Sequence::class . ' of array skipped until offset 2')
        );
    }

    /**
     * @test
     */
    public function containsReferenceToBothLimitAndSkippedElements()
    {
        assertThat(
                (string) Sequence::infinite(1, function($i) { return ++$i; })
                        ->skip(2)
                        ->limit(3),
                equals(
                        Sequence::class . ' starting at 1 continued by a lambda function'
                        . ' skipped until offset 2 limited to 3 elements'
                )
        );
    }

    /**
     * @test
     */
    public function limitDescriptionWithBothLimitAndSkipped()
    {
        assertThat(
                (new Limit(new \ArrayIterator([]), 2, 3))->description(),
                equals('limited to 3 elements starting from offset 2')
        );
    }
}
