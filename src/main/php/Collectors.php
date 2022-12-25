<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\sequence;
/**
 * Provides factory functions for common collectors.
 *
 * @since 5.2.0
 */
class Collectors
{
    /**
     * @internal create an instance with $sequence->collect() instead
     */
    public function __construct(private Sequence $sequence) {  }

    /**
     * collects all elements into structure defined by supplier
     *
     * @api
     * @param   callable  $supplier     returns a fresh structure to collect elements into
     * @param   callable  $accumulator  accumulates elements into structure
     * @param   callable  $finisher     optional  final operation after all elements have been added to the structure
     * @return  mixed
     */
    public function with(
        callable $supplier,
        callable $accumulator,
        ?callable $finisher = null
    ) {
        return $this->sequence->collect(new Collector($supplier, $accumulator, $finisher));
    }

    /**
     * returns a collector for lists
     *
     * @api
     * @return mixed[]
     */
    public function inList(): array
    {
        return $this->sequence->collect(Collector::forList());
    }

    /**
     * returns a collector for maps
     *
     * @api
     * @return array<string,mixed>
     */
    public function inMap(?callable $selectKey = null, ?callable $selectValue = null): array
    {
        return $this->sequence->collect(Collector::forMap($selectKey, $selectValue));
    }

    /**
     * creates collector which groups the elements in two partitions according to given predicate
     *
     * @api
     * @param  callable   $predicate  function to evaluate in which partition an element belongs
     * @param  Collector  $base       optional  defaults to Collector::forList()
     * @return array<bool,mixed[]>
     */
    public function inPartitions(callable $predicate, ?Collector $base = null): array
    {
        $collector = (null === $base) ? Collector::forList() : $base;
        return $this->with(
                /**
                 * @return array<bool,Collector>
                 */
                fn() => [
                    true  => $collector->fork(),
                    false => $collector->fork()
                ],
                function(array &$partitions, mixed $element, int|string $key) use($predicate): void
                {
                    $partitions[$predicate($element)]->accumulate($element, $key);
                },
                /**
                 * @param  array<bool,Collector> $partitions
                 * @return array<bool,mixed[]>
                 */
                fn(array $partitions) => [
                    true  => $partitions[true]->finish(),
                    false => $partitions[false]->finish()
                ]
        );
    }

    /**
     * creates collector which groups the elements according to given classifier
     *
     * @api
     * @param  callable  $classifier function to map elements to keys
     * @param  Collector $base       optional  defaults to Collector::forList()
     * @return array<mixed>
     */
    public function inGroups(callable $classifier, Collector $base = null): array
    {
        $collector = $base ?: Collector::forList();
        return $this->with(
                fn() => [],
                /**
                 * @param array<Collector> $groups
                 * @param mixed            $element
                 */
                function(array &$groups, mixed $element) use($classifier, $collector): void
                {
                    $key = $classifier($element);
                    if (!isset($groups[$key])) {
                        $groups[$key] = $collector->fork();
                    }

                    $groups[$key]->accumulate($element, $key);
                },
                /**
                 * @param  array<Collector> $groups
                 * @return mixed[]
                 */
                function(array $groups): array
                {
                    foreach ($groups as $key => $group) {
                        $groups[$key] = $group->finish();
                    }

                    return $groups;
                }
        );
    }

    /**
     * creates collector which concatenates all elements into a single string
     *
     * If no key separator is provided keys will not be part of the resulting
     * string.
     * <code>
     * Sequence::of(['foo' => 303, 'bar' => 808, 'baz'=> 909])
     *         ->collect()
     *         ->byJoining(); // results in '303, 808, 9090'
     *
     * Sequence::of(['foo' => 303, 'bar' => 808, 'baz'=> 909])
     *         ->collect()
     *         ->byJoining(', ', '', '', ': '); // results in 'foo: 303, bar: 808, baz: 9090'
     * </code>
     *
     * @api
     * @param string $delimiter    delimiter between elements, defaults to ', '
     * @param string $prefix       optional  prefix for complete string, empty by default
     * @param string $suffix       optional  suffix for complete string, empty by default
     * @param string $keySeparator optional  separator between key and element
     */
    public function byJoining(
        string $delimiter = ', ',
        string $prefix = '',
        string $suffix = '',
        ?string $keySeparator = null
    ): string {
        return $this->with(
                fn () => '',
                function(string &$joinedElements, string $element, $key) use($prefix, $delimiter, $keySeparator): void
                {
                    if (strlen($joinedElements) === 0) {
                        $joinedElements = $prefix;
                    } else {
                        $joinedElements .= $delimiter;
                    }

                    $joinedElements .= (null !== $keySeparator ? $key . $keySeparator : '') . $element;
                },
                fn(string $joinedElements) => $joinedElements . $suffix
        );
    }
}
