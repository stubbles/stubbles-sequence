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
use bovigo\assert\predicate\Predicate;
use SebastianBergmann\Exporter\Exporter;
use stubbles\sequence\Sequence;

use function bovigo\assert\predicate\equals;
/**
 * Predicate which checks that a sequence provides all expected values or data.
 *
 * @since  8.0.0
 */
class Provides extends Predicate
{
    /**
     * @type  Predicate
     */
    private $expected;
    /**
     * @type  string
     */
    private $what;

    /**
     * creates instance which checks that a sequence provides given values ignoring the keys
     *
     * @param   array  $expected
     * @return  Provides
     */
    public static function values(array $expected): self
    {
        return new self($expected, 'values');
    }

    /**
     * creates instance which checks that a sequence provides values with keys
     *
     * @param   array  $expected
     * @return  Provides
     */
    public static function data(array $expected): self
    {
        return new self($expected, 'data');
    }

    private function __construct(array $expected, string $what)
    {
        $this->expected = equals($expected);
        $this->what     = $what;
    }

    public function test($value): bool
    {
        if (!($value instanceof Sequence)) {
            throw new \InvalidArgumentException(
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

    public function describeValue(Exporter $exporter, $value): string
    {
        if ($value instanceof Sequence) {
            return $value->__toString();
        }

        return parent::describeValue($exporter, $value);
    }
}
