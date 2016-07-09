stubbles/sequence
=================

Stream your iterators.


Build status
------------

[![Build Status](https://secure.travis-ci.org/stubbles/stubbles-sequence.png)](http://travis-ci.org/stubbles/stubbles-sequence) [![Coverage Status](https://coveralls.io/repos/stubbles/stubbles-sequence/badge.png?branch=master)](https://coveralls.io/r/stubbles/stubbles-sequence?branch=master)

[![Latest Stable Version](https://poser.pugx.org/stubbles/sequence/version.png)](https://packagist.org/packages/stubbles/sequence) [![Latest Unstable Version](https://poser.pugx.org/stubbles/sequence/v/unstable.png)](//packagist.org/packages/stubbles/sequence)


Installation
------------

_stubbles/sequence_ is distributed as [Composer](https://getcomposer.org/)
package. To install it as a dependency of your package use the following
command:

    composer require "stubbles/sequence": "^8.0"


Requirements
------------

_stubbles/sequence_ requires at least PHP 7.0. For PHP 5.6 use _stubbles/sequence_ 7.0


Introduction
------------

Sequence operations are divided into intermediate and terminal operations,
and are combined to form pipelines. A pipeline consists of a source (such as
a collection, an array, a generator function, or an I/O channel); followed by
zero or more intermediate operations such as `Sequence::filter()` or
`Sequence::map()`; and a terminal operation such as `Sequence::each()` or
`Sequence::reduce()`.

Intermediate operations return a new Sequence. They are always lazy;
executing an intermediate operation such as `Sequence::filter()` does not
actually perform any filtering, but instead creates a new Sequence that, when
traversed, contains the elements of the initial stream that match the given
predicate. Traversal of the pipeline source does not begin until the terminal
operation of the pipeline is executed.

Terminal operations, such as `Sequence::each()` or `Sequence::reduce()`, may
traverse the Sequence to produce a result or a side-effect. After the
terminal operation is performed, the pipeline is considered consumed, and can
no longer be used; if you need to traverse the same data source again, you
must return to the data source to get a new Sequence. In almost all cases,
terminal operations are eager, completing their traversal of the data source
and processing of the pipeline before returning. Only the terminal operation
`Sequence::getIterator()` is not; this is provided as an "escape hatch" to
enable arbitrary client-controlled pipeline traversals in the event that the
existing operations are not sufficient to the task.


Create a sequence
-----------------

### `Sequence::of($elements)`

Creates sequence of given `$elements` which can be either a `\Traversable` or an
array.


### `Sequence::infinite($seed, callable $operation)`

Creates an infinite sequence. With `$seed` the initial value can be specified,
while `$operation` must be callable which takes the current value and generates
the next value.

Warning: calling terminal operations on an infinite sequence result in endless
loops trying to calculate the terminal value. Before calling a terminal
operation the sequence should be limited via `Sequence::limit()`. Alternatively
you can iterate over the sequence itself and stop the iteration when required.


### `Sequence::generate($seed, callable $operation, callable $validator)`

Creates a sequence which generates values while being worked on.

The sequence ends when the provided validator returns `false` for the first time.
The validator receives two values: the last generated value, and the amount of
values already generated.

The following example generates an array which has $start as first value, where
each following value is incremented by 2, and the amount of values in the array
is either maximal 100 or PHP_INT_MAX has been reached:

```php
Sequence::generate(
     $start,
     function($previous) { return $previous + 2; },
     function($value, $invocations) { return $value < (PHP_INT_MAX - 1) &&  100 >= $invocations; }
)->values();
```


Intermediate operations
-----------------------

### `limit($n)`

Limits sequence to the first n elements, i.e. stops iteration when the nth
element is reached.

```php
Sequence::of([1, 2, 3, 4, 5, 6, 7, 8, 9, 10])->limit(3)->data();
```

Result: `[1, 2, 3]`


### `skip($n)`

Skips the first n elements of the sequence.

```php
Sequence::of([1, 2, 3, 4, 5, 6, 7, 8, 9, 10])->skip(10)->data();
```
Result: `[11]`


### `filter(callable $predicate)`

Returns a new sequence with elements matching the given predicate. The given
predicate reveives a value and must return true to accept the value or false to
reject the value.

```php
Sequence::of([1, 2, 3, 4, 5, 6, 7, 8, 9, 10])
        ->filter(function($value) { return $value % 2 === 0;})
        ->data();
```

Result: `[2, 4, 6, 8, 10]`


### `map(callable $valueMapper, callable $keyMapper = null)`

Returns a new sequence which maps each element using the given mapper.

```php

```


### `mapKeys(callable $keyMapper)`

Returns a new sequence which maps each key using the given mapper.

```php

```


### `append($other)`

Appends any value, creating a new combined sequence.

In case given $other is not something iterable it is simply appended as last
element to a new sequence.

```php
Sequence::of([1, 2])->append([3, 4]); // results in new sequence with [1, 2, 3, 4]
```



### `peek(callable $valueConsumer, callable $keyConsumer = null)`

Allows consumer to receive the value before any further operations are applied.

You can use it to inspect the values and keys before any further operations are
applied. This is especially useful when you need to debug the contents of a
sequence.

```php
Sequence::of([1, 2, 3, 4])->peek('var_dump');
```


Terminal operations
-------------------

### `each(callable $consumer)`

Invokes consumer for each element and returns the amount of invocations.

The consumer receives the element as first value, and the key as second:

```php
Sequence::of(['foo' => 'bar'])->each(
        function($element, $key)
        {
            // do something with $element and $key
        }
);
```

The key is optional and can be left away:
```php
Sequence::of(['foo' => 'bar'])->each(
        function($element)
        {
            // do something with $element and $key
        }
);
```

Iteration can be stopped by returning `false` from the consumer. The following
example stops when it reaches element 2:
```php
Sequence::of([1, 2, 3, 4])->each(
        function($element)
        {
            echo $element . "\n";
            return (2 <= $element);
        }
);
```


### `first()`

Returns first element of sequence.

```php
echo Sequence::of(['foo', 'bar', 'baz'])->first(); // displays 'foo'
```


### `reduce(callable $accumulate = null, $identity = null)`

Reduces all elements of the sequence to a single value. The given callable will
receive two values: the current reduced value which on first invocation is the
value of `$identity`, and the current element as second. It needs to calculate
and return a new value from both which becomes the new value of identity, and
will be returned from `reduce()` after the last element has been processed.

```php
Sequence::of([1, 2, 3, 4])->reduce(function($identity, $b) { return $identity + $b; });
```

In case no callable is provided an instance of `stubbles\sequence\Reducer` will
be returned which provides convenience methods for some common reduction
operations.

#### `reduce()->toSum(callable $summer = null)`

Reduces sequence to the sum of all elements. By default assumes the sequence
consists of numbers and simply adds them one after another.

```php
Sequence::of([1, 2, 3, 4])->reduce()->toSum();
```

In case the sequence consists of other types a callable can be passed that can
calculate the sum instead. The callable must expect two values: the sum
calculated until now and a single element. The return value must be the new sum
with the given element.

```php
Sequence::of(['a', 'b', 'c', 'd'])->reduce()->toSum(
        function($sum, $element)
        {
            return $sum + ord($element);
        }
);
```

#### `reduce()->toMin(callable $min = null)`

Reduces sequence to the smallest element. By default assumes the sequence
consists of numbers.

```php
Sequence::of([1, 2, 3, 4])->reduce()->toMin();
```

In case the sequence consists of other types a callable can be passed that can
calculate the smallest value instead. The callable must expect two values: the
smalles value found until until now (which `null`  on the first invocation) and
a single element. The return value must be the smaller of both arguments.

```php
Sequence::of(['a', 'b', 'c', 'd'])->reduce()->toSum(
        function($smallest, $element)
        {
            return (null === $smallest || ord($element) < ord($smallest)) ? $element : $smallest;
        }
);
```


#### `reduce()->toMax(callable $max = null)`

Reduces sequence to the greatest element. By default assumes the sequence
consists of numbers.

```php
Sequence::of([1, 2, 3, 4])->reduce()->toMax();
```

In case the sequence consists of other types a callable can be passed that can
calculate the greatest value instead. The callable must expect two values: the
greatest value found until until now (which `null`  on the first invocation) and
a single element. The return value must be the greater of both arguments.

```php
Sequence::of(['a', 'b', 'c', 'd'])->reduce()->toMax(
        function($greatest, $element)
        {
            return (null === $greatest || ord($element) > ord($greatest)) ? $element : $greatest;
        }
);
```


### `collect(Collector $collector = null)`

Collects all elements into a structure defined by given collector.

A collector accumulates elements into a structure, optionally transforming the
result into a final representation.

In case no collector is provided an instance of `stubbles\sequence\Collectors`
will be returned which provides convenience methods for some common collector
operations.

#### `collect()->inList()`

Returns the values of the sequence as array.

```php
Sequence::of(['foo' => 'bar', 'dummy' => 'baz'])->collect()->inList(); // returns ['bar', 'baz']
```

#### `collect()->inMap(callable $selectKey = null, callable $selectValue = null)`

Returns the sequence data with keys and values as associative array. The
`$selectKey` callable will be used to determine the key for a value in the new
map, and `$selectValue` will be used to determine the value. If they are omitted
the key and value from the source elements will be used as they are.

```php
$people= [
        1549 => new Employee(1549, 'Timm', 'B', 15),
        1552 => new Employee(1552, 'Alex', 'I', 14),
        6100 => new Employee(6100, 'Dude', 'I', 4)
];
$employees = Sequence::of($people)->collect()->inMap(
        function(Employee $e) { return $e->id(); },
        function(Employee $e) { return $e->name(); }
); // results in [1549 => 'Timm', 1552 => 'Alex', 6100 => 'Dude']
```

#### `collect()->inPartitions(callable $predicate, Collector $base = null)`

Groups the elements in two partitions according to given predicate.

```php
$timm = new Employee(1549, 'Timm', 'B', 15);
$alex = new Employee(1552, 'Alex', 'I', 14);
$dude = new Employee(6100, 'Dude', 'I', 4);
$employees = Sequence::of([$timm, $alex, $dude])->collect()->inPartitions(
        function(Employee $e) { return $e->years() > 10; }
);  // results in [true  => [$timm, $alex], false => [$dude]]
```

The second argument can be used to influence the actual partition value.


```php
$timm = new Employee(1549, 'Timm', 'B', 15);
$alex = new Employee(1552, 'Alex', 'I', 14);
$dude = new Employee(6100, 'Dude', 'I', 4);
$employees = Sequence::of([$timm, $alex, $dude])->collect()->inPartitions(
        function(Employee $e) { return $e->years() > 10; },
        Collector::forAverage(function(Employee $e) { return $e->years(); })
);  // results in [true  => 14.5, false => 4]
```


#### `collect()->inGroups(callable $classifier, Collector $base = null)`

Groups the elements in two partitions according to given predicate.

```php
$timm = new Employee(1549, 'Timm', 'B', 15);
$alex = new Employee(1552, 'Alex', 'I', 14);
$dude = new Employee(6100, 'Dude', 'I', 4);
$employees = Sequence::of([$timm, $alex, $dude])->collect()->inGroups(
        function(Employee $e) { return $e->department(); }
); // results in ['B' => [$timm], 'I' => [$alex, $dude]]
```

The second argument can be used to influence the actual group value:

```php
$timm = new Employee(1549, 'Timm', 'B', 15);
$alex = new Employee(1552, 'Alex', 'I', 14);
$dude = new Employee(6100, 'Dude', 'I', 4);
$employees = Sequence::of([$timm, $alex, $dude])->collect()->inGroups(
        function(Employee $e) { return $e->department(); },
        Collector::forSum(function(Employee $e) { return $e->years(); })
); // results in ['B' => 15, 'I' => 18]
```

#### `collect()->byJoining($delimiter = ', ', $prefix = '', $suffix = '', $keySeparator = null)`

Concatenates all elements into a single string.

```php
$timm = new Employee(1549, 'Timm', 'B', 15);
$alex = new Employee(1552, 'Alex', 'I', 14);
$dude = new Employee(6100, 'Dude', 'I', 4);
$employees = Sequence::of([$timm, $alex, $dude])
        ->map(function(Employee $e) { return $e->name(); })
        ->collect()
        ->byJoining();
// results in 'Timm, Alex, Dude'
```

When `$keySeparator` is supplied the key will also be included:

```php
$timm = new Employee(1549, 'Timm', 'B', 15);
$alex = new Employee(1552, 'Alex', 'I', 14);
$dude = new Employee(6100, 'Dude', 'I', 4);
$employees = Sequence::of([1549 => $timm, 1552 => $alex, 6100 => $dude])
        ->map(function(Employee $e) { return $e->name(); })
        ->collect()
        ->byJoining(', ', '(', ')', ':');
// results in '(1549:Timm, 1552:Alex, 6100:Dude)'
```


### `count()`

Returns number of elements in sequence.

```php
echo Sequence::of(['foo', 'bar', 'baz'])->count(); // displays 3
```

As Sequence is also an instance of `\Countable` it can also be used with PHP's
native `count()` function:

```php
echo count(Sequence::of(['foo', 'bar', 'baz'])); // displays 3
```


### `values()`

Returns the values of the sequence as array, shortcut for `collect()->inList()`.

```php
Sequence::of(['foo' => 'bar', 'dummy' => 'baz'])->values(); // returns ['bar', 'baz']
```


### `data()`

Returns the sequence data with keys and values as associative array. Shortcut
for `collect()->inMap()`.

```php
Sequence::of(['foo' => 'bar', 'dummy' => 'baz'])->data(); // returns ['foo' => 'bar', 'dummy' => 'baz']
```
