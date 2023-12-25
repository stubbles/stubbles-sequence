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

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use function bovigo\assert\assertThat;
use function bovigo\assert\predicate\equals;
/**
 * Tests for stubbles\sequence\iterator\Generator.
 *
 * @since 5.2.0
 */
#[Group('iterator')]
class GeneratorTest extends TestCase
{
    #[Test]
    public function iterationStopsWhenValidatorReturnsFalse(): void
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

        assertThat(
            $result,
            equals([0 => 2, 1 => 4, 2 => 6, 3 => 8, 4 => 10, 5 => 12, 6 => 14])
        );
    }

    #[Test]
    public function resultsAreReproducableWhenOperationIsReproducable(): void
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

        assertThat($result1, equals($result2));
    }

    #[Test]
    public function infiniteGeneratorDoesStopOnlyWhenBreakOutOfLoop(): void
    {
        $i = 0;
        foreach (Generator::infinite(0, fn($value) => $value + 2) as $key => $value) {
            if (1000 > $key) {
                $i++;
            } else {
                break;
            }
        }

        assertThat($i, equals(1000));
    }
}
