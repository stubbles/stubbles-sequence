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
namespace stubbles\sequence\assert;
use stubbles\sequence\Sequence;

use function bovigo\assert\assert;
use function bovigo\assert\assertFalse;
use function bovigo\assert\assertTrue;
use function bovigo\assert\expect;
use function bovigo\assert\exporter;
use function bovigo\assert\predicate\equals;
/**
 * Tests for stubbles\sequence\assert\Provides.
 *
 * @since  8.0.0
 * @group  assert
 */
class ProvidesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function throwInvalidArgumentExceptionWhenTestValueIsNotOfTypeSequence()
    {
        $provides  = Provides::values([]);
        expect(function() use ($provides) { $provides->test(new \stdClass()); })
                ->throws(\InvalidArgumentException::class)
                ->withMessage('Given value of type "object" is not an instance of ' . Sequence::class);
    }

    public function createProvides(): array
    {
        return [
                [Provides::values([1]), 'values'],
                [Provides::data(['foo' => 1]), 'data']
        ];
    }

    /**
     * @test
     * @dataProvider  createProvides
     */
    public function evaluatesToTrueIfTestedSequenceProvidesExpectedContents(Provides $provides)
    {
        assertTrue($provides->test(Sequence::of(['foo' => 1])));
    }

    /**
     * @test
     * @dataProvider  createProvides
     */
    public function evaluatesToFalseIfTestedSequenceDoesNotProvideExpectedContents(Provides $provides)
    {
        assertFalse($provides->test(Sequence::of(['bar' => 2])));
    }

    /**
     * @test
     * @dataProvider  createProvides
     */
    public function evaluatesToFalseIfTestedSequenceContainsMoreValues(Provides $provides)
    {
        assertFalse($provides->test(Sequence::of(['foo' => 1, 'bar' => 2])));
    }

    /**
     * @test
     * @dataProvider  createProvides
     */
    public function stringRepresentationReferencesType(Provides $provides, $type)
    {
        assert((string) $provides, equals('provides expected ' . $type));
    }

    /**
     * @test
     */
    public function stringRepresentationContainsDiffWhenTestFailedForValues()
    {
        $provides = Provides::values([1]);
        $provides->test(Sequence::of(['foo' => 1, 'bar' => 2]));
        assert(
                (string) $provides,
                equals('provides expected values.
--- Expected
+++ Actual
@@ @@
 Array (
     0 => 1
+    1 => 2
 )
')
        );
    }

    /**
     * @test
     */
    public function stringRepresentationContainsDiffWhenTestFailedForData()
    {
        $provides = Provides::data(['foo' => 1]);
        $provides->test(Sequence::of(['foo' => 1, 'bar' => 2]));
        assert(
                (string) $provides,
                equals('provides expected data.
--- Expected
+++ Actual
@@ @@
 Array (
     \'foo\' => 1
+    \'bar\' => 2
 )
')
        );
    }

    /**
     * @test
     */
    public function exportsSequenceAsStringRepresentation()
    {
        assert(
                Provides::values([1])->describeValue(exporter(), Sequence::of([])),
                equals(Sequence::class . ' of array')
        );
    }

    /**
     * @test
     */
    public function exportsAnyThingElseWithDefault()
    {
        assert(
                Provides::values([1])->describeValue(exporter(), 'foo'),
                equals('\'foo\'')
        );
    }
}
