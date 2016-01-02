<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\sequence
 */
namespace stubbles\sequence {

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
    function ensureCallable(callable $callable)
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
}
