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
use bovigo\assert\predicate\Equals;
use bovigo\assert\predicate\Predicate;
use SebastianBergmann\Exporter\Exporter;
use stubbles\sequence\Sequence;
/**
 * Predicate which checks that a sequence provides all expected values or data.
 *
 * @since 8.0.0
 */
class Provides extends Predicate
{
    private Equals $expected;
    private string $what;

    /**
     * creates instance which checks that a sequence provides given values ignoring the keys
     *
     * @param mixed[] $expected
     */
    public static function values(array $expected): self
    {
        return new self($expected, 'values');
    }

    /**
     * creates instance which checks that a sequence provides values with keys
     *
     * @param  array<int|string,mixed> $expected
     */
    public static function data(array $expected): self
    {
        return new self($expected, 'data');
    }

    /**
     * @param array<int|string,mixed> $expected
     */
    private function __construct(array $expected, string $what)
    {
        $this->expected = new Equals($expected);
        $this->what     = $what;
    }

    /**
     * Tests that given value is a sequence and contains all values.
     *
     * @throws InvalidArgumentException
     */
    public function test(mixed $value): bool
    {
        if (!($value instanceof Sequence)) {
            throw new InvalidArgumentException(
                    'Given value of type "' . gettype($value)
                    . '" is not an instance of ' . Sequence::class
            );
        }

        return $this->expected->test($value->{$this->what}());
    }

    public function __toString(): string
    {
        $return = 'provides expected ' . $this->what;
        if ($this->expected->hasDiffForLastFailure()) {
            $return .= '.' . $this->expected->diffForLastFailure();
        }

        return $return;
    }

    /**
     * Describes given value in textual form.
     */
    public function describeValue(Exporter $exporter, mixed $value): string
    {
        if ($value instanceof Sequence) {
            return $value->__toString();
        }

        return parent::describeValue($exporter, $value);
    }
}
