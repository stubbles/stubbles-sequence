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
/**
 * Enhances PHP's LimitIterator with a useful description.
 *
 * @since  8.0.0
 * @internal
 */
class Limit extends \LimitIterator implements SequenceUtility
{
    /**
     * @type  string
     */
    private $description;

    public function __construct(\Iterator $iterator, int $offset = 0, int $count = -1)
    {
        parent::__construct($iterator, $offset, $count);
        if (0 === $offset) {
            $this->description = 'limited to first ' . $count . ' elements of ';
        } elseif (-1 === $count) {
            $this->description = 'skipping first ' . $offset . ' elements of ';
        } else {
            $this->description = 'limited to ' . $count
                    . ' elements starting at offset ' . $offset . ' of ';
        }
    }

    /**
     * returns description of this iterator
     *
     * @return  string
     * @since   8.0.0
     */
    public function description(): string
    {
        return $this->description;
    }
}
