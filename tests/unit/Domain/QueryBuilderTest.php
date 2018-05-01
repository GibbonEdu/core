<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
*/

namespace Gibbon\Domain;

use PHPUnit\Framework\TestCase;

/**
 * @covers QueryBuilder
 */
class QueryBuilderTest extends TestCase
{
    private $query;

    public function setUp()
    {
        $this->select = new QueryBuilder();
    }

    public function testCanBuildEmptyQuery()
    {
        $this->assertEquals('', $this->select->build());
    }

    public function testCanSelectByString()
    {
        $this->select->cols('foo')->from('bar');
        $this->assertEquals("SELECT foo FROM bar", $this->select->build());
    }

    public function testCanSelectByArray()
    {
        $this->select->cols(['foo', 'baz'])->from('bar');
        $this->assertEquals("SELECT foo, baz FROM bar", $this->select->build());
    }

    public function testCanLimit()
    {
        $this->select->cols('foo')->from('bar')->limit(3);
        $this->assertEquals("SELECT foo FROM bar LIMIT 3", $this->select->build());
    }

    public function testCanOffset()
    {
        $this->select->cols('foo')->from('bar')->offset(42);
        $this->assertEquals("SELECT foo FROM bar OFFSET 42", $this->select->build());
    }
}
