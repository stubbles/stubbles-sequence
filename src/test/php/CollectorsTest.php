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
use PHPUnit\Framework\TestCase;
use stubbles\test\sequence\Employee;

use function bovigo\assert\assertEmptyString;
use function bovigo\assert\assertThat;
use function bovigo\assert\predicate\equals;
/**
 * Tests for stubbles\sequence\Collectors.
 *
 * @since  5.2.0
 */
class CollectorsTest extends TestCase
{
    /**
     * @var  array<int,Employee>
     */
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
    public function joiningEmptySequenceReturnsEmptyString(): void
    {
        assertEmptyString(Sequence::of([])->collect()->byJoining());
    }

    /**
     * @test
     */
    public function joiningNames(): void
    {
        assertThat(
                Sequence::of($this->people)
                        ->map(function(Employee $e) { return $e->name(); })
                        ->collect()
                        ->byJoining(),
                equals('Timm, Alex, Dude')
        );
    }

    /**
     * @test
     */
    public function joiningNamesWithSemicolon(): void
    {
        assertThat(
                Sequence::of($this->people)
                        ->map(function(Employee $e) { return $e->name(); })
                        ->collect()
                        ->byJoining(';'),
                equals('Timm;Alex;Dude')
        );
    }

    /**
     * @test
     */
    public function joiningNamesWithPrefixAndSuffix(): void
    {
        assertThat(
                Sequence::of($this->people)
                        ->map(function(Employee $e) { return $e->name(); })
                        ->collect()
                        ->byJoining(', ', '(', ')'),
                equals('(Timm, Alex, Dude)')
        );
    }

    /**
     * @test
     */
    public function joiningNamesWithKeySeparator(): void
    {
        assertThat(
                Sequence::of($this->people)
                        ->map(function(Employee $e) { return $e->name(); })
                        ->collect()
                        ->byJoining(', ', '(', ')', ':'),
                equals('(1549:Timm, 1552:Alex, 6100:Dude)')
        );
    }

    /**
     * @test
     */
    public function groupingBy(): void
    {
        assertThat(
                Sequence::of($this->people)
                        ->collect()
                        ->inGroups(function(Employee $e) { return $e->department(); }),
                equals([
                        'B' => [$this->people[1549]],
                        'I' => [$this->people[1552], $this->people[6100]]
                ])
        );
    }

    /**
     * @test
     */
    public function groupingByWithSummingOfYears(): void
    {
        assertThat(
                Sequence::of($this->people)
                        ->collect()
                        ->inGroups(
                                function(Employee $e) { return $e->department(); },
                                Collector::forSum(function(Employee $e) { return $e->years(); })
                          ),
                equals(['B' => 15, 'I' => 18])
        );
    }

    /**
     * @test
     */
    public function groupingByWithAveragingOfYears(): void
    {
        assertThat(
                Sequence::of($this->people)
                        ->collect()
                        ->inGroups(
                                function(Employee $e) { return $e->department(); },
                                Collector::forAverage(function(Employee $e) { return $e->years(); })
                        ),
                equals(['B' => 15, 'I' => 9])
        );
    }

    /**
     * @test
     */
    public function partitioningBy(): void
    {
        assertThat(
                Sequence::of($this->people)
                        ->collect()
                        ->inPartitions(function(Employee $e) { return $e->years() > 10; }),
                equals([
                        true  => [$this->people[1549], $this->people[1552]],
                        false => [$this->people[6100]]
                ])
        );
    }

    /**
     * @test
     */
    public function partitioningByWithSum(): void
    {
        assertThat(
                Sequence::of($this->people)->collect()->inPartitions(
                        function(Employee $e) { return $e->years() > 10; },
                        Collector::forSum(function(Employee $e) { return $e->years(); })
                ),
                equals([
                        true  => 29,
                        false => 4
                ])
        );
    }
}
