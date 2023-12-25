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

use ArrayIterator;
use PHPUnit\Framework\TestCase;
use stubbles\sequence\iterator\Limit;

use function bovigo\assert\assertThat;
use function bovigo\assert\predicate\equals;
/**
 * Tests for stubbles\sequence\Sequence->__toString().
 *
 * @since 8.0.0
 */
class SequenceToStringTest extends TestCase
{
    /**
     * @return array<array<mixed>>
     */
    public static function sequenceSourceTypes(): array
    {
        $f = function() { yield 1; yield 2; yield 3; };
        return [
                [[1, 2, 3], 'of array'],
                [new ArrayIterator([1, 2, 3]), 'from ArrayIterator'],
                [Sequence::of([1, 2, 3]), 'of array'],
                [$f(), 'from Generator']
        ];
    }

    /**
     * @param iterable<int> $input
     * @test
     * @dataProvider sequenceSourceTypes
     */
    public function containsSourceType(iterable $input, string $expectedSourceType): void
    {
        assertThat(
                (string) Sequence::of($input),
                equals(Sequence::class . ' ' . $expectedSourceType)
        );
    }

    /**
     * @test
     */
    public function containsReferenceToFilterLambdaFunction(): void
    {
        assertThat(
                (string) Sequence::of(1, 2, 3, 4)
                        ->filter(fn($e) => 0 === $e % 2),
                equals(Sequence::class . ' of array filtered by a lambda function')
        );
    }

    /**
     * @test
     */
    public function containsReferenceToFilterNamedFunction(): void
    {
        assertThat(
                (string) Sequence::of('Hello', 1337, 'World')->filter('is_string'),
                equals(Sequence::class . ' of array filtered by is_string()')
        );
    }

    /**
     * @test
     */
    public function containsReferenceToMappingLambdaFunction(): void
    {
        assertThat(
                (string) Sequence::of([1, 2, 3, 4])->map(function($e) { return $e * 2; }),
                equals(Sequence::class . ' of array values mapped by a lambda function')
        );
    }

    public static function map1(int $e): int
    {
        return $e * 2;
    }

    /**
     * @test
     */
    public function containsReferenceToMappingStaticMethod(): void
    {
        assertThat(
                (string) Sequence::of([1, 2, 3, 4])->map([__CLASS__, 'map1']),
                equals(Sequence::class . ' of array values mapped by ' . __CLASS__ . '::map1()')
        );
    }

    public static function map2(int $e): int
    {
        return $e * 2;
    }

    /**
     * @test
     */
    public function containsReferenceToMappingInstanceMethod(): void
    {
        assertThat(
                (string) Sequence::of([1, 2, 3, 4])->map([$this, 'map2']),
                equals(Sequence::class . ' of array values mapped by ' . __CLASS__ . '->map2()')
        );
    }

    /**
     * @test
     */
    public function containsReferenceToMappingNamedFunction(): void
    {
        assertThat(
                (string) Sequence::of([1.9, 2.5, 3.1])->map('floor'),
                equals(Sequence::class . ' of array values mapped by floor()')
        );
    }

    /**
     * @test
     */
    public function containsReferenceToKeyMappingFunction(): void
    {
        assertThat(
                (string) Sequence::of([1, 2, 3, 4])
                        ->mapKeys(fn($e) => $e * 2),
                equals(Sequence::class . ' of array keys mapped by a lambda function')
        );
    }

    /**
     * @test
     */
    public function containsNoReferenceToPeakFunction(): void
    {
        assertThat(
                (string) Sequence::of([1, 2, 3, 4])->peek('var_export'),
                equals(Sequence::class . ' of array')
        );
    }

    /**
     * @test
     */
    public function containsReferenceToLimit(): void
    {
        assertThat(
                (string)  Sequence::of([1, 2, 3])->limit(2),
                equals(Sequence::class . ' of array limited to 2 elements')
        );
    }

    /**
     * @test
     */
    public function containsReferenceToInfiniteGenerator(): void
    {
        assertThat(
                (string) Sequence::infinite(1, fn($i) => ++$i)->limit(2),
                equals(
                        Sequence::class . ' starting at 1 continued by a lambda function'
                        . ' limited to 2 elements'
                )
        );
    }

    /**
     * @test
     */
    public function containsReferenceToGenerator(): void
    {
        assertThat(
                (string) Sequence::generate(
                        1,
                        fn($i) => $i + 1,
                        fn($i) => $i < 10
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
    public function containsReferenceToSkippedElements(): void
    {
        assertThat(
                (string) Sequence::of(4, 5, 6)->skip(2),
                equals(Sequence::class . ' of array skipped until offset 2')
        );
    }

    /**
     * @test
     */
    public function containsReferenceToBothLimitAndSkippedElements(): void
    {
        assertThat(
                (string) Sequence::infinite(1, fn($i) => ++$i)
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
    public function limitDescriptionWithBothLimitAndSkipped(): void
    {
        assertThat(
                (new Limit(new ArrayIterator([]), 2, 3))->description(),
                equals('limited to 3 elements starting from offset 2')
        );
    }
}
