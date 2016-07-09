<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * The contents of this file draw heavily from XP Framework
 * https://github.com/xp-forge/sequence
 *
 * Copyright (c) 2001-2014, XP-Framework Team
 * All rights reserved.
 * https://github.com/xp-framework/xp-framework/blob/master/core/src/main/php/LICENCE
 *
 * @package  stubbles\sequence
 */
namespace stubbles\sequence;
use stubbles\sequence\iterator\Filter;
use stubbles\sequence\iterator\Generator;
use stubbles\sequence\iterator\Limit;
use stubbles\sequence\iterator\MappingIterator;
use stubbles\sequence\iterator\Peek;
use stubbles\sequence\iterator\SequenceUtility;
/**
 * Sequence is a stream of data that can be operated on.
 *
 * Sequence operations are divided into intermediate and terminal operations,
 * and are combined to form pipelines. A pipeline consists of a source (such as
 * a Collection, an array, a generator function, or an I/O channel); followed by
 * zero or more intermediate operations such as Sequence::filter() or
 * Sequence::map(); and a terminal operation such as Sequence::each() or
 * Sequence::reduce().
 *
 * Intermediate operations return a new Sequence. They are always lazy;
 * executing an intermediate operation such as Sequence::filter() does not
 * actually perform any filtering, but instead creates a new Sequence that, when
 * traversed, contains the elements of the initial stream that match the given
 * predicate. Traversal of the pipeline source does not begin until the terminal
 * operation of the pipeline is executed.
 *
 * Terminal operations, such as Sequence::each() or Sequence::reduce(), may
 * traverse the Sequence to produce a result or a side-effect. After the
 * terminal operation is performed, the pipeline is considered consumed, and can
 * no longer be used; if you need to traverse the same data source again, you
 * must return to the data source to get a new Sequence. In almost all cases,
 * terminal operations are eager, completing their traversal of the data source
 * and processing of the pipeline before returning. Only the terminal operation
 * Sequence::getIterator() is not; this is provided as an "escape hatch" to
 * enable arbitrary client-controlled pipeline traversals in the event that the
 * existing operations are not sufficient to the task.
 *
 * @api
 * @since  5.2.0
 */
class Sequence implements \IteratorAggregate, \Countable, \JsonSerializable
{
    /**
     * actual data in sequence
     *
     * @type  \Traversable|array  $elements
     */
    private $elements;
    /**
     * @type  string
     * @since  8.0.0
     */
    private $type     = '';

    /**
     * constructor
     *
     * @param  \Traversable|array  $elements
     * @param  string              $sourceType  optional
     */
    private function __construct($elements, string $sourceType = null)
    {
        $this->elements = $elements;
        if (null !== $sourceType) {
            $this->type = $sourceType . ' ';
        }

        if (is_array($elements)) {
            $this->type .= 'of array';
        } elseif ($elements instanceof SequenceUtility) {
            $this->type .= $elements->description();
        } else {
            $this->type .= 'from ' . get_class($elements);
        }
    }

    /**
     * creates sequence of given data
     *
     * @param   \stubbles\sequence\Sequence|\Traversable|array  $elements
     * @return  \stubbles\sequence\Sequence
     * @throws  \InvalidArgumentException
     */
    public static function of($elements): self
    {
        if ($elements instanceof self) {
            return $elements;
        }

        if ($elements instanceof \Traversable || is_array($elements)) {
            return new self($elements);
        }

        throw new \InvalidArgumentException(
                'Given data must either be a ' . __CLASS__
                . ', an instance of \Traversable or an array but is '
                . gettype($elements)
        );
    }

    /**
     * creates an infinite sequence
     *
     * Warning: calling terminal operations on an infinite sequence result in
     * endless loops trying to calculate the terminal value. Before calling a
     * terminal operation the sequence should be limited via limit().
     * Alternatively you can iterate over the sequence itself and stop the
     * iteration when required.
     *
     *
     * @param   $seed     $seed       initial value
     * @param   callable  $operation  operation which takes a value and generates a new one
     * @return  \stubbles\sequence\Sequence
     */
    public static function infinite($seed, callable $operation): self
    {
        return new self(Generator::infinite($seed, $operation));
    }

    /**
     * creates a sequence which generates values while being worked on
     *
     * The sequence ends when the provided validator returns false for the first
     * time. The validator receives two values: the last generated value, and
     * the amount of values already generated.
     *
     * The following example generates an array which has $start as first value,
     * where each following value is incremented by 2, and the amount of values
     * in the array is either maximal 100 or PHP_INT_MAX has been reached:
     * <code>
     * Sequence::generate(
     *      $start,
     *      function($previous) { return $previous + 2; },
     *      function($value, $invocations) { return $value &lt; (PHP_INT_MAX - 1) &&  100 &gt;= $invocations; }
     * )->values();
     * </code>
     *
     * @param   $seed     $seed       initial value
     * @param   callable  $operation  operation which takes a value and generates a new one
     * @param   callable  $validator  function which decides whether a value is valid
     * @return  \stubbles\sequence\Sequence
     */
    public static function generate($seed, callable $operation, callable $validator): self
    {
        return new self(new Generator($seed, $operation, $validator));
    }

    /**
     * limits sequence to the first n elements
     *
     * This is an intermediate operation.
     *
     * @param   int  $n
     * @return  \stubbles\sequence\Sequence
     */
    public function limit(int $n): self
    {
        return new self(new Limit($this->getIterator(), 0, $n), $this->type);
    }

    /**
     * skips the first n elements of the sequence
     *
     * This is an intermediate operation.
     *
     * @param   int  $n
     * @return  \stubbles\sequence\Sequence
     */
    public function skip(int $n): self
    {
        return new self(new Limit($this->getIterator(), $n), $this->type);
    }

    /**
     * returns a new sequence with elements matching the given predicate
     *
     * This is an intermediate operation.
     *
     * The given predicate reveives a value and must return true to accept the
     * value or false to reject the value.
     *
     * @param   callable  $predicate
     * @return  \stubbles\sequence\Sequence
     */
    public function filter(callable $predicate): self
    {
        return new self(
                new Filter($this->getIterator(), $predicate),
                $this->type
        );
    }

    /**
     * returns a new sequence which maps each element using the given mapper
     *
     * This is an intermediate operation.
     *
     * @param   callable  $valueMapper  function to map values with
     * @param   callable  $keyMapper    function to map keys with
     * @return  \stubbles\sequence\Sequence
     */
    public function map(callable $valueMapper, callable $keyMapper = null): self
    {
        return new self(
                new MappingIterator(
                        $this->getIterator(),
                        $valueMapper,
                        $keyMapper
                ),
                $this->type
        );
    }

    /**
     * returns a new sequence which maps each key using the given mapper
     *
     * This is an intermediate operation.
     *
     * @param   callable  $keyMapper    function to map keys with
     * @return  \stubbles\sequence\Sequence
     * @since   5.3.0
     */
    public function mapKeys(callable $keyMapper): self
    {
        return new self(
                new MappingIterator(
                        $this->getIterator(),
                        null,
                        $keyMapper
                ),
                $this->type
        );
    }

    /**
     * appends any value, creating a new combined sequence
     *
     * In case given $other is not something iterable it is simply appended as
     * last element to a new sequence.
     *
     * This is an intermediate operation.
     *
     * @param   mixed  $other
     * @return  \stubbles\sequence\Sequence
     */
    public function append($other): self
    {
        if ($other instanceof self) {
            $otherIterator = $other->getIterator();
        } elseif ($other instanceof \Iterator) {
            $otherIterator = $other;
        } elseif ($other instanceof \Traversable) {
            $otherIterator = new \IteratorIterator($other);
        } elseif (is_array($other)) {
            $otherIterator = new \ArrayIterator($other);
        } elseif (is_array($this->elements)) {
            $all = $this->elements;
            $all[] = $other;
            return new self($all);
        } else {
            $otherIterator = new \ArrayIterator([$other]);
        }

        $appendIterator = new \AppendIterator();
        $appendIterator->append($this->getIterator());
        $appendIterator->append($otherIterator);
        return new self($appendIterator, $this->type);
    }

    /**
     * allows consumer to receive the value before any further operations are applied
     *
     * This is an intermediate operation.
     *
     * @param   callable  $valueConsumer  consumer which is invoked with each element
     * @param   callable  $keyConsumer    optional  consumer which is invoked with each key
     * @return  \stubbles\sequence\Sequence
     */
    public function peek(callable $valueConsumer, callable $keyConsumer = null): self
    {
        return new self(
                new Peek($this->getIterator(), $valueConsumer, $keyConsumer),
                $this->type
        );
    }

    /**
     * invokes consumer for each element
     *
     * This is a terminal operation.
     *
     * The consumer receives the element as first value, and the key as second:
     * <code>
     * Sequence::of(['foo' => 'bar'])->each(
     *         function($element, $key)
     *         {
     *              // do something with $element
     *         }
     * );
     * </code>
     *
     * The key is optional and can be left away:
     * <code>
     * Sequence::of([1, 2, 3, 4])->each(
     *         function($element)
     *         {
     *              // do something with $element
     *         }
     * );
     * </code>
     *
     * Iteration can be stopped by returning false from the consumer. The
     * following example stops when it reaches element 2:
     * <code>
     * Sequence::of([1, 2, 3, 4])->each(
     *         function($element)
     *         {
     *             echo $element . "\n";
     *             return (2 <= $element);
     *         }
     * );
     * </code>
     *
     *
     * @param   callable  $consumer
     * @return  int       amount of elements for which consumer was invoked
     */
    public function each(callable $consumer): int
    {
        $calls = 0;
        foreach ($this->elements as $key => $element) {
            $calls++;
            if (false === $consumer($element, $key)) {
                break;
            }
        }

        return $calls;
    }

    /**
     * returns first element of sequence
     *
     * This is a terminal operation.
     *
     * @return  mixed
     * @XmlIgnore
     */
    public function first()
    {
        foreach ($this->elements as $first) {
            return $first;
        }

        return null;
    }

    /**
     * reduces all elements of the sequence to a single value
     *
     * This is a terminal operation.
     *
     * In case no callable is provided an instance of \stubbles\sequence\Reducer
     * will be returned which provides convenience methods for some common
     * reduction operations.
     *
     * @param   callable  $accumulate  optional  function which acumulates result and element to a new result
     * @param   mixed     $identity    optional  initial return value in case sequence is empty, defaults to null
     * @return  mixed|\stubbles\sequence\Reducer
     */
    public function reduce(callable $accumulate = null, $identity = null)
    {
        if (null === $accumulate) {
            return new Reducer($this);
        }

        $result = $identity;
        foreach ($this->elements as $key => $element) {
            $result = $accumulate($result, $element, $key);
        }

        return $result;
    }

    /**
     * collects all elements into a structure defined by given collector
     *
     * This is a terminal operation.
     *
     * In case no collector is provided an instance of \stubbles\sequence\Collectors
     * will be returned which provides convenience methods for some common
     * collection operations.
     *
     * @param   \stubbles\sequence\Collector  $collector  optional
     * @return  mixed|\stubbles\sequence\Collectors
     */
    public function collect(Collector $collector = null)
    {
        if (null === $collector) {
            return new Collectors($this);
        }

        foreach ($this->elements as $key => $element) {
            $collector->accumulate($element, $key);
        }

        return $collector->finish();
    }

    /**
     * returns number of elements in sequence
     *
     * This is a terminal operation.
     *
     * @return  int
     * @XmlIgnore
     */
    public function count(): int
    {
        $amount = 0;
        // iterate with $key so the key consumer from peek() can have a look
        foreach ($this->elements as $key => $element) {
            $amount++;
        }

        return $amount;
    }

    /**
     * returns the values of the sequence
     *
     * This is a terminal operation.
     *
     * @return  array
     * @XmlIgnore
     */
    public function values(): array
    {
        return $this->collect()->inList();
    }

    /**
     * returns the sequence data with keys and values
     *
     * This is a terminal operation.
     *
     * @return  array
     */
    public function data(): array
    {
        return $this->collect()->inMap();
    }

    /**
     * returns an iterator on this sequence
     *
     * @return  \Traversable
     * @XmlIgnore
     */
    public function getIterator(): \Traversable
    {
        if ($this->elements instanceof \Iterator) {
            return $this->elements;
        }

        if ($this->elements instanceof \Traversable) {
            return new \IteratorIterator($this->elements);
        }

        return new \ArrayIterator($this->elements);
    }

    /**
     * returns string description of this sequence
     *
     * @return  string
     * @since   8.0.0
     */
    public function __toString(): string
    {
        return __CLASS__ . ' ' . $this->type;
    }

    /**
     * returns serializable representation for JSON
     *
     * @return  array
     * @since   5.3.2
     */
    public function jsonSerialize(): array
    {
        return $this->data();
    }
}
