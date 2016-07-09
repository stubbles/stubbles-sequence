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
namespace stubbles\sequence\iterator;
use function bovigo\assert\assert;
use function bovigo\assert\predicate\equals;
/**
 * Tests for stubbles\sequence\iterator\Generator.
 *
 * @group  iterator
 * @since  5.2.0
 */
class GeneratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function iterationStopsWhenValidatorReturnsFalse()
    {
        $generator = new Generator(
                2,
                function($value) { return $value + 2; },
                function($value) { return $value < 15; }
        );
        $result = [];
        foreach ($generator as $key => $value) {
            $result[$key] = $value;
        }

        assert(
                $result,
                equals([0 => 2, 1 => 4, 2 => 6, 3 => 8, 4 => 10, 5 => 12, 6 => 14])
        );
    }

    /**
     * @test
     */
    public function resultsAreReproducableWhenOperationIsReproducable()
    {
        $generator = new Generator(
                2,
                function($value) { return $value + 2; },
                function($value) { return $value < 15; }
        );
        $result1 = [];
        foreach ($generator as $key => $value) {
            $result1[$key] = $value;
        }

        $result2 = [];
        foreach ($generator as $key => $value) {
            $result2[$key] = $value;
        }

        assert($result1, equals($result2));
    }

    /**
     * @test
     */
    public function infiniteGeneratorDoesStopOnlyWhenBreakOutOfLoop()
    {
        $i = 0;
        foreach (Generator::infinite(0, function($value) { return $value + 2; }) as $key => $value) {
            if (1000 > $key) {
                $i++;
            } else {
                break;
            }
        }

        assert($i, equals(1000));
    }
}
