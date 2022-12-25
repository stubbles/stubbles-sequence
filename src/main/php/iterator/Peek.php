<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 */
namespace stubbles\sequence\iterator;

use Iterator;
use IteratorIterator;
/**
 * Iterator which allows consumption of an element before iteration continues.
 *
 * @since 5.2.0
 */
class Peek extends IteratorIterator implements SelfDescribing
{
    /**
     * consumer for values
     *
     * @var callable
     */
    private $valueConsumer;
    /**
     * consumer for keys
     *
     * @var callable|null
     */
    private $keyConsumer;

    /**
     * constructor
     *
     * @param Iterator $iterator iterator to map values of
     * @param callable $valueConsumer consumer which is invoked with current value
     * @param callable $keyConsumer   optional  consumer which is invoked with current key
     */
    public function __construct(
        Iterator $iterator,
        callable $valueConsumer,
        ?callable $keyConsumer = null
    ) {
        parent::__construct($iterator);
        $this->valueConsumer = $valueConsumer;
        $this->keyConsumer   = $keyConsumer;
    }

    /**
     * returns the current element
     */
    public function current(): mixed
    {
        $consumeValue = $this->valueConsumer;
        $current = parent::current();
        $consumeValue($current);
        return $current;
    }

    /**
     * returns the current key
     */
    public function key(): mixed
    {
        $key = parent::key();
        if (null !== $this->keyConsumer) {
            $consumeKey = $this->keyConsumer;
            $consumeKey($key);
        }

        return $key;
    }

    /**
     * returns description of this iterator
     *
     * Description is intentionally empty, peeking does not change the
     * elements which it is peeking at.
     */
    public function description(): string
    {
        return '';
    }
}
