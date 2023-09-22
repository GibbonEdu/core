<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
*/

namespace Gibbon\Domain;

use PHPUnit\Framework\TestCase;
use Gibbon\Domain\DataSet;

/**
 * @covers DataSet
 */
class DataSetTest extends TestCase
{
    public function testCreateFromArray()
    {
        $data = new DataSet(range(0, 10));

        $this->assertEquals(11, $data->count());
        $this->assertEquals(11, $data->getResultCount());
        $this->assertEquals(11, $data->getTotalCount());
    }

    public function testSetsResultCount()
    {
        $data = new DataSet([]);
        $data->setResultCount(30, 100);

        $this->assertEquals(30, $data->getResultCount());
        $this->assertEquals(100, $data->getTotalCount());
    }

    public function testSetsPagination()
    {
        $data = new DataSet([]);
        $data->setPagination(3, 42);

        $this->assertEquals(3, $data->getPage());
        $this->assertEquals(42, $data->getPageSize());
    }

    public function testIsIterable()
    {
        $data = new DataSet(range(0, 10));
        $count = 0;
        foreach ($data as $item) {
            $count++;
        }

        $this->assertEquals(11, $count);
    }

    public function testCanCheckIsSubset()
    {
        $data = (new DataSet([]))->setResultCount(42, 42);
        $this->assertFalse($data->isSubset());

        $data = (new DataSet([]))->setResultCount(10, 42);
        $this->assertTrue($data->isSubset());
    }

    public function testCanCheckIsFirstPage()
    {
        $data = (new DataSet(range(0, 25)));
        $this->assertTrue($data->isFirstPage());

        $data = (new DataSet(range(0, 25)))->setPagination(2, 10);
        $this->assertFalse($data->isFirstPage());
    }

    public function testCanCheckIsLastPage()
    {
        $data = (new DataSet(range(0, 25)))->setPagination(2, 10);
        $this->assertFalse($data->isLastPage());

        $data = (new DataSet(range(0, 25)))->setPagination(3, 10);
        $this->assertTrue($data->isLastPage());
    }

    public function testCalculatesPageCount()
    {
        $data = new DataSet(range(0, 25));
        $data->setPagination(1, 10);

        $this->assertEquals(3, $data->getPageCount());
    }

    public function testCalculatesPageBounds()
    {
        $data = new DataSet(range(0, 25));
        $data->setPagination(2, 10);

        $this->assertEquals(11, $data->getPageFrom());
        $this->assertEquals(20, $data->getPageTo());
    }

    public function testCanGetRow()
    {
        $data = new DataSet([
            ['foo' => 10, 'bar' => 11, 'baz' => 12],
            ['foo' => 20, 'bar' => 21, 'baz' => 22],
            ['foo' => 30, 'bar' => 31, 'baz' => 32],
        ]);

        $this->assertEquals(['foo' => 20, 'bar' => 21, 'baz' => 22], $data->getRow(1));
    }

    public function testCanGetColumn()
    {
        $data = new DataSet([
            ['foo' => 10, 'bar' => 11, 'baz' => 12],
            ['foo' => 20, 'bar' => 21, 'baz' => 22],
            ['foo' => 30, 'bar' => 31, 'baz' => 32],
        ]);

        $this->assertEquals([11,21,31], $data->getColumn('bar'));
    }

    public function testCanJoinColumn()
    {
        $data = new DataSet([
            ['foo' => 10, 'bar' => 11, 'baz' => 12],
            ['foo' => 20, 'bar' => 21, 'baz' => 22],
            ['foo' => 30, 'bar' => 31, 'baz' => 32],
        ]);

        $join = [
            10 => 'thing1',
            20 => 'thing2',
            30 => 'thing3',
        ];

        $data->joinColumn('foo', 'things', $join);

        $this->assertArrayHasKey('things', $data->getRow(1));
        $this->assertContains('thing2', $data->getRow(1));
    }
}
