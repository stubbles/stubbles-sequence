<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\sequence {

    /**
     * cast given value into an array
     *
     * The following casts are applied:
     * - array: returned as is
     * - instance of \Traversable: return value from iterator_to_array()
     * - object with asArray() method: returns value from call to this method
     * - object with toArray() method: returns value from call to this method
     * - object: returns map of properties using extractObjectProperties()
     * - any other: returns array with value as single entry
     *
     * @param   mixed  $value
     * @return  array
     * @since   5.4.0
     */
    function castToArray($value): array
    {
        if ($value instanceof \Traversable) {
            return iterator_to_array($value);
        } elseif (is_array($value)) {
            return $value;
        } elseif (is_object($value)) {
            if (method_exists($value, 'asArray')) {
                return $value->asArray();
            } elseif (method_exists($value, 'toArray')) {
                return $value->toArray();
            }

            return extractObjectProperties($value);
        }

        return [$value];
    }

    /**
     * cast any value into an iterator
     *
     * The following rules apply:
     *  - instances of stubbles\sequence\Sequence are casted using $value->getIterator()
     *  - instances of \Iterator are returned as they arguments
     *  - instances of \Traversable are wrapped with \IteratorIterator
     *  - arrays are wrapped with \ArrayIterator
     *  - any other value is put into an array wrapped with \ArrayIterator
     *
     * @param   mixed  $value
     * @return  \Iterator
     * @since   8.0.0
     */
    function castToIterator($value): \Iterator
    {
        if ($value instanceof Sequence) {
            return $value->getIterator();
        }

        if ($value instanceof \Iterator) {
            return $value;
        }

        if ($value instanceof \Traversable) {
            return new \IteratorIterator($value);
        }

        if (is_array($value)) {
            return new \ArrayIterator($value);
        }

        return new \ArrayIterator([$value]);
    }

    /**
     * method to extract all properties regardless of their visibility
     *
     * This is a workaround for the problem that as of PHP 5.2.4 get_object_vars()
     * is not any more capable of retrieving private properties from child classes.
     * See http://stubbles.org/archives/32-Subtle-BC-break-in-PHP-5.2.4.html.
     *
     * @param   object  $object
     * @return  array
     * @since   3.1.0
     */
    function extractObjectProperties($object): array
    {
        $properties      = (array) $object;
        $fixedProperties = [];
        foreach ($properties as $propertyName => $propertyValue) {
            if (!strstr($propertyName, "\0")) {
                $fixedProperties[$propertyName] = $propertyValue;
                continue;
            }

            $fixedProperties[substr($propertyName, strrpos($propertyName, "\0") + 1)] = $propertyValue;
        }

        return $fixedProperties;
    }

    /**
     * ensures that the given callable is really callable
     *
     * Internal functions like strlen() or is_string() require an *exact* amount
     * of arguments. If they receive too many arguments they just report an error.
     * This is a problem when you pass such a function as callable to a place
     * where it will receive more than it's exact amount of arguments.
     *
     * @param   callable  $callable
     * @return  callable
     * @since   4.0.0
     */
    function ensureCallable(callable $callable): callable
    {
        if (!is_string($callable)) {
            return $callable;
        }

        static $wrappedFunctions = [];
        if (isset($wrappedFunctions[$callable])) {
            return $wrappedFunctions[$callable];
        }

        $function = new \ReflectionFunction($callable);
        if (!$function->isInternal()) {
            return $callable;
        }

        $signature = $arguments = '';
        foreach ($function->getParameters() as $position => $param) {
            $signature .= ', ' . ($param->isPassedByReference() ? '&' : '') . '$_' . $position . ($param->isOptional() ? '= null' : '');
            $arguments .= ', $_' . $position;
        }

        $wrappedFunctions[$callable] = eval(
                'return function(' . substr($signature, 2) . ') { return ' . $callable . '(' . substr($arguments, 2) . '); };'
        );

        return $wrappedFunctions[$callable];
    }

    /**
     * tries to determine a name for the callable
     *
     * @return  string
     * @since   8.0.0
     */
    function describeCallable(callable $callable): string
    {
        if (is_array($callable)) {
            if (is_string($callable[0])) {
                return $callable[0] . '::' . $callable[1] . '()';
            }

            return get_class($callable[0]) . '->' . $callable[1] . '()';
        } elseif (is_string($callable)) {
            return $callable . '()';
        }

        return 'a lambda function';
    }
}
