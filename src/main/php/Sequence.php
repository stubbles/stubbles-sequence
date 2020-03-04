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
 */
namespace stubbles\sequence;
use stubbles\sequence\iterator\{
    Filter,
    Generator,
    Limit,
    MappingIterator,
    Peek,
    SelfDescribing
};
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
 * @template T
 */
class Sequence implements \IteratorAggregate, \Countable, \JsonSerializable
{
    /**
     * actual data in sequence
     *
     * @var  iterable<T>  $elements
     */
    private $elements;
    /**
     * @var  string
     * @since  8.0.0
     */
    private $type     = '';

    /**
     * constructor
     *
     * @param  iterable<T>  $elements
     * @param  string       $sourceType  optional
     */
    private function __construct($elements, string $sourceType = '')
    {
        $this->elements = $elements;
        if ($elements instanceof SelfDescribing) {
            $this->type = $sourceType . ' ' . $elements->description();
        } elseif (\is_array($elements)) {
            $this->type = 'of array';
        } else {
            $this->type = 'from ' . \get_class($elements);
        }
    }

    /**
     * creates sequence of given data
     *
     * The result depends on the arguments:
     * - no arguments: equivalent to Sequence::of([])
     * - one argument which is an instance of Sequence: exactly this sequence
     * - one argument which is an array or a Traversable: sequence of this
     * - one argument which is none of the above: equivalent to Sequence::of([$element])
     * - two or more arguments: sequence of the list of arguments
     *
     * @param   T|T[]|Sequence<T>  $elements
     * @return  \stubbles\sequence\Sequence<T>
     */
    public static function of(...$elements): self
    {
        if (\count($elements) === 1) {
            $elements = $elements[0];
        }

        if ($elements instanceof self) {
            return $elements;
        }

        if ($elements instanceof \Traversable || \is_array($elements)) {
            return new self($elements);
        }

        return new self([$elements]);
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
     * @param   T               $seed       initial value
     * @param   callable(T): T  $operation  operation which takes a value and generates a new one
     * @return  \stubbles\sequence\Sequence<T>
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
     * @param   T                  $seed       initial value
     * @param   callable(T): T     $operation  operation which takes a value and generates a new one
     * @param   callable(T): bool  $validator  function which decides whether a value is valid
     * @return  \stubbles\sequence\Sequence<T>
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
     * @return  \stubbles\sequence\Sequence<T>
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
     * @return  \stubbles\sequence\Sequence<T>
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
     * @param   callable(T): bool  $predicate
     * @return  \stubbles\sequence\Sequence<T>
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
     * @template V
     * @param   callable(T, int|string): V             $valueMapper  function to map values with
     * @param   (callable(int|string, T): int|string)  $keyMapper    function to map keys with
     * @return  \stubbles\sequence\Sequence<V>
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
     * @param   callable(int|string, T): string  $keyMapper  function to map keys with
     * @return  \stubbles\sequence\Sequence<T>
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
     * @param   T|T[]|Sequence<T>  $other
     * @return  \stubbles\sequence\Sequence<T>
     */
    public function append($other): self
    {
        if (is_array($this->elements) && !is_array($other) && !($other instanceof \Traversable)) {
            $all = $this->elements;
            $all[] = $other;
            return new self($all);
        }

        $appendIterator = new \AppendIterator();
        $appendIterator->append($this->getIterator());
        $appendIterator->append(castToIterator($other));
        return new self($appendIterator, $this->type);
    }

    /**
     * allows consumer to receive the value before any further operations are applied
     *
     * This is an intermediate operation.
     *
     * @param   callable(T): void           $valueConsumer  consumer which is invoked with each element
     * @param   callable(int|string): void  $keyConsumer    optional  consumer which is invoked with each key
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
     * @param   callable(T, int|string): bool  $consumer
     * @return  int  amount of elements for which consumer was invoked
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
     * @return  T|null
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
     * provides access to common collection operations
     *
     * @return  \stubbles\sequence\Collectors
     */
    public function collect(): Collectors
    {
        return new Collectors($this);
    }

    /**
     * collects all elements into a structure defined by given collector
     * 
     * This is a terminal operation.
     *
     * @template V
     * @param   \stubbles\sequence\Collector<T,V>  $collector  optional
     * @return  V
     */
    public function collectWith(Collector $collector)
    {
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
     * @return  T[]
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
     * @return  array<string,T>
     */
    public function data(): array
    {
        return $this->collect()->inMap();
    }

    /**
     * returns an iterator on this sequence
     *
     * @return  \Iterator<T>
     * @XmlIgnore
     */
    public function getIterator(): \Iterator
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
        return __CLASS__ . ' ' . trim($this->type);
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
