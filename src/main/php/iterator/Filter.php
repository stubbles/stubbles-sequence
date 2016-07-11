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
use function stubbles\sequence\ensureCallable;
/**
 * Enhances PHP's CallbackFilterIterator with a useful description.
 *
 * @since  8.0.0
 * @internal
 */
class Filter extends \CallbackFilterIterator implements SelfDescribing
{
    /**
     * @type  string
     */
    private $description;

    public function __construct(\Iterator $iterator, callable $callback)
    {
        parent::__construct($iterator, ensureCallable($callback));
        $this->description = describeCallable($callback);
    }

    /**
     * returns description of this iterator
     *
     * @return  string
     * @since   8.0.0
     */
    public function description(): string
    {
        return 'filtered by ' . $this->description;
    }
}
