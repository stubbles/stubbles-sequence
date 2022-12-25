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
use function stubbles\sequence\describeCallable;
/**
 * Iterator which calls an operation to retrieve the value.
 *
 * @since 5.2.0
 */
class Generator implements \Iterator, SelfDescribing
{
    /**
     * current value
     *
     * @var mixed
     */
    private $value;
    /**
     * number of delivered elements since last rewind
     */
    private int $elementsGenerated = 0;
    /**
     * operation which takes a value and generates a new one
     *
     * @var callable
     */
    private $operation;
    /**
     * function which decides whether a value is valid
     *
     * @var callable
     */
    private $validator;

    /**
     * constructor
     *
     * @param mixed    $seed      initial value
     * @param callable $operation operation which takes a value and generates a new one
     * @param callable $validator function which decides whether a value is valid
     */
    public function __construct(
        private mixed $seed,
        callable $operation,
        callable $validator
    ) {
        $this->value     = $seed;
        $this->operation = $operation;
        $this->validator = $validator;
    }

    /**
     * creates a generator which iterates infinitely
     */
    public static function infinite(mixed $seed, callable $operation): self
    {
        return new self($seed, $operation, function() { return true; });
    }

    /**
     * returns the current generated value
     */
    public function current(): mixed
    {
        return $this->value;
    }

    /**
     * returns number of delivered elements since last rewind()
     */
    public function key(): int
    {
        return $this->elementsGenerated;
    }

    /**
     * generates next value
     */
    public function next(): void
    {
        $operation   = $this->operation;
        $this->value = $operation($this->value);
        $this->elementsGenerated++;
    }

    /**
     * resets number of delivered elements to 0 and restarts with initial seed
     */
    public function rewind(): void
    {
        $this->elementsGenerated = 0;
        $this->value             = $this->seed;
    }

    /**
     * checks if current element is valid
     */
    public function valid(): bool
    {
        $validate = $this->validator;
        return $validate($this->value, $this->elementsGenerated);
    }

    /**
     * returns description of this iterator
     */
    public function description(): string
    {
        return 'starting at ' . (string) $this->seed . ' continued by '
         . describeCallable($this->operation);
    }
}
