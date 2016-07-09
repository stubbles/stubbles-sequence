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
use function stubbles\sequence\ensureCallable;
/**
 * Maps values and/or keys from an underlying iterator.
 *
 * @since  5.0.0
 */
class MappingIterator extends \IteratorIterator
{
    /**
     * callable which maps the values‚
     *
     * @type  callable
     */
    private $valueMapper;
    /**
     * callable which maps the keys
     *
     * @type  callable
     */
    private $keyMapper;

    /**
     * constructor
     *
     * @param   \Traversable  $iterator     iterator to map values of
     * @param   callable      $valueMapper  optional  callable which maps the values
     * @param   callable      $keyMapper    optional  callable which maps the keys
     * @throws  \InvalidArgumentException  in case both $valueMapper and $keyMapper are null
     */
    public function __construct(
            \Traversable $iterator,
            callable $valueMapper = null,
            callable $keyMapper = null
    ) {
        if (null === $valueMapper && null === $keyMapper) {
            throw new \InvalidArgumentException(
                    'Passed null for both valueMapper and keyMapper, but at '
                    . 'least one of both must not be null'
            );
        }

        parent::__construct($iterator);
        $this->valueMapper = null !== $valueMapper ? ensureCallable($valueMapper) : null;
        $this->keyMapper   = null !== $keyMapper ? ensureCallable($keyMapper) : null;
    }

    /**
     * returns the current element
     *
     * @return  mixed
     */
    public function current()
    {
        if (!$this->valid()) {
            return null;
        }

        if (null === $this->valueMapper) {
            return parent::current();
        }

        $map = $this->valueMapper;
        return $map(parent::current(), parent::key());
    }

    /**
     * returns the current key
     *
     * @return  mixed
     */
    public function key()
    {
        if (!$this->valid()) {
            return null;
        }

        if (null === $this->keyMapper) {
            return parent::key();
        }

        $map = $this->keyMapper;
        return $map(parent::key(), parent::current());
    }
}
