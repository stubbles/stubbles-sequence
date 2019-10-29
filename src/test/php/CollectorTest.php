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
use PHPUnit\Framework\TestCase;
use stubbles\test\sequence\Employee;

use function bovigo\assert\assertThat;
use function bovigo\assert\predicate\equals;
/**
 * Tests for stubbles\sequence\Collector.
 *
 * @since  5.2.0
 */
class CollectorTest extends TestCase
{
    private $people;

    protected function setUp(): void
    {
        $this->people= [
            1549 => new Employee(1549, 'Timm', 'B', 15),
            1552 => new Employee(1552, 'Alex', 'I', 14),
            6100 => new Employee(6100, 'Dude', 'I', 4)
        ];
    }

    /**
     * @test
     */
    public function toList()
    {
        assertThat(
                Sequence::of($this->people)
                        ->map(function($e) { return $e->name(); })
                        ->collect()
                        ->inList(),
                equals(['Timm', 'Alex', 'Dude'])
        );
    }

    /**
     * @test
     */
    public function toMapUsesGivenKeyAndValueSelector()
    {
        assertThat(
                Sequence::of($this->people)
                        ->collect()
                        ->inMap(
                            function(Employee $e) { return $e->id(); },
                            function(Employee $e) { return $e->name(); }
                ),
                equals([1549 => 'Timm', 1552 => 'Alex', 6100 => 'Dude'])
        );
    }

    /**
     * @test
     */
    public function toMapPassesKeyAndValueWhenNoSelectorProvided()
    {
        assertThat(
                Sequence::of($this->people)->collect()->inMap(),
                equals($this->people)
        );
    }
}
