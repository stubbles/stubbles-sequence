<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\sequence\assert;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stubbles\sequence\Sequence;

use function bovigo\assert\assertThat;
use function bovigo\assert\assertFalse;
use function bovigo\assert\assertTrue;
use function bovigo\assert\expect;
use function bovigo\assert\exporter;
use function bovigo\assert\predicate\equals;
/**
 * Tests for stubbles\sequence\assert\Provides.
 *
 * @since 8.0.0
 */
#[Group('assert')]
class ProvidesTest extends TestCase
{
    #[Test]
    public function throwInvalidArgumentExceptionWhenTestValueIsNotOfTypeSequence(): void
    {
        $provides  = Provides::values([]);
        expect(function() use ($provides) { $provides->test(new \stdClass()); })
            ->throws(InvalidArgumentException::class)
            ->withMessage('Given value of type "object" is not an instance of ' . Sequence::class);
    }

    public static function createProvides(): iterable
    {
        yield [Provides::values([1]), 'values'];
        yield [Provides::data(['foo' => 1]), 'data'];
    }

    #[Test]
    #[DataProvider('createProvides', validateArgumentCount:false)]
    public function evaluatesToTrueIfTestedSequenceProvidesExpectedContents(
        Provides $provides
    ): void {
        assertTrue($provides->test(Sequence::of(['foo' => 1])));
    }

    #[Test]
    #[DataProvider('createProvides', validateArgumentCount:false)]
    public function evaluatesToFalseIfTestedSequenceDoesNotProvideExpectedContents(
        Provides $provides
    ): void {
        assertFalse($provides->test(Sequence::of(['bar' => 2])));
    }

    #[Test]
    #[DataProvider('createProvides', validateArgumentCount:false)]
    public function evaluatesToFalseIfTestedSequenceContainsMoreValues(
        Provides $provides
    ): void {
        assertFalse($provides->test(Sequence::of(['foo' => 1, 'bar' => 2])));
    }

    #[Test]
    #[DataProvider('createProvides')]
    public function stringRepresentationReferencesType(
        Provides $provides,
        string $type
    ): void {
        assertThat((string) $provides, equals('provides expected ' . $type));
    }

    #[Test]
    public function stringRepresentationContainsDiffWhenTestFailedForValues(): void
    {
        $provides = Provides::values([1]);
        $provides->test(Sequence::of(['foo' => 1, 'bar' => 2]));
        assertThat(
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

    #[Test]
    public function stringRepresentationContainsDiffWhenTestFailedForData(): void
    {
        $provides = Provides::data(['foo' => 1]);
        $provides->test(Sequence::of(['foo' => 1, 'bar' => 2]));
        assertThat(
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

    #[Test]
    public function exportsSequenceAsStringRepresentation(): void
    {
        assertThat(
            Provides::values([1])->describeValue(exporter(), Sequence::of([])),
            equals(Sequence::class . ' of array')
        );
    }

    #[Test]
    public function exportsAnyThingElseWithDefault(): void
    {
        assertThat(
            Provides::values([1])->describeValue(exporter(), 'foo'),
            equals('\'foo\'')
        );
    }
}
