<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\sequence\iterator;
/**
 * Enhances PHP's LimitIterator with a useful description.
 *
 * @since  8.0.0
 * @internal
 */
class Limit extends \LimitIterator implements SelfDescribing
{
    private $offset;
    private $count;

    public function __construct(\Iterator $iterator, int $offset = 0, int $count = -1)
    {
        parent::__construct($iterator, $offset, $count);
        $this->offset = $offset;
        $this->count  = $count;
    }

    /**
     * returns description of this iterator
     *
     * @return  string
     * @since   8.0.0
     */
    public function description(): string
    {
        if (0 === $this->offset) {
            return 'limited to ' . $this->count . ' elements';
        }

        if (-1 === $this->count) {
            return 'skipped until offset ' . $this->offset;
        }

        return 'limited to ' . $this->count
         . ' elements starting from offset ' . $this->offset;
    }
}
