<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\test\sequence;
/**
 * Helper class for the test.
 */
class Employee
{
    private $id;
    private $name;
    private $department;
    private $years;

    public function __construct(int $id, string $name, string $department, int $years)
    {
        $this->id         = $id;
        $this->name       = $name;
        $this->department = $department;
        $this->years      = $years;
    }

    public function id(): int { return $this->id; }

    public function name(): string { return $this->name; }

    public function department(): string { return $this->department; }

    public function years(): int { return $this->years; }

    public function toString(): string
    {
        return __CLASS__.'('.
          'id= '.$this->id.', name= '.$this->name.', department= '.$this->department.', years= '.$this->years.
        ')';
    }
}