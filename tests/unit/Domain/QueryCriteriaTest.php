<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
*/

namespace Gibbon\Domain;

use PHPUnit\Framework\TestCase;
use Gibbon\Domain\QueryCriteria;

/**
 * @covers QueryCriteria
 */
class QueryCriteriaTest extends TestCase
{
    /**
     * Empty criteria object, created for each test.
     *
     * @var QueryCriteria
     */
    private $criteria;

    public function setUp()
    {
        $this->criteria = new QueryCriteria();
    }

    public function testCanBeCreated()
    {
        $this->assertInstanceOf(QueryCriteria::class, new QueryCriteria());
    }

    public function testCanLoadFromArray()
    {
        $values = array(
            'page' => 5,
            'pageSize' => 42,
            'searchBy' => [],
            'filterBy' => [],
            'sortBy' => [],
            // 'searchBy' => array('columnName' => 'foo bar'),
            // 'filterBy' => array('foo:bar'),
            // 'sortBy' => array('columnName' => 'DESC'),
        );

        $this->assertEquals($values, $this->criteria->fromArray($values)->toArray());
    }

    public function testCanLoadFromJson()
    {
        $values = '{"page":3,"pageSize":24,"searchBy":[],"filterBy":[],"sortBy":[]}';

        $this->assertEquals($values, $this->criteria->fromJson($values)->toJson());
    }



    public function testCanSetPageNumber()
    {
        $this->criteria->page(5);

        $this->assertEquals(5, $this->criteria->getPage());
    }

    public function testCanSetPageSize()
    {
        $this->criteria->pageSize(42);

        $this->assertEquals(42, $this->criteria->getPageSize());
    }   

    public function testCanSearchByOneColumn()
    {
        $this->criteria->searchBy('columnName', 'foo bar');

        $this->assertEquals('foo bar', $this->criteria->getSearch('columnName'));
    }

    public function testCanSearchByManyColumns()
    {
        $this->criteria->searchBy(['column1', 'column2', 'column3'], 'foo bar');

        $this->assertEquals('foo bar', $this->criteria->getSearch('column1'));
        $this->assertEquals('foo bar', $this->criteria->getSearch('column2'));
        $this->assertEquals('foo bar', $this->criteria->getSearch('column3'));
    }

    public function testCanCheckIfCriteriaHasSearch()
    {
        $this->assertFalse($this->criteria->hasSearch());

        $this->criteria->searchBy('columnName', 'foo bar');

        $this->assertTrue($this->criteria->hasSearch());
    }

    public function testCanGetAllSearches()
    {
        $this->criteria->searchBy('columnName', 'foo bar');

        $this->assertTrue(is_array($this->criteria->getSearch()));
    }

    public function testCanFilterByString()
    {
        $this->criteria->filterBy('foo:bar');

        $this->assertContains('foo:bar', $this->criteria->getFilters());
    }

    public function testCanFilterByNameValuePair()
    {
        $this->criteria->filterBy('foo', 'bar');

        $this->assertContains('foo:bar', $this->criteria->getFilters());
    }

    public function testCanCheckIfCriteriaHasFilters()
    {
        $this->assertFalse($this->criteria->hasFilters());

        $this->criteria->filterBy('foo:bar');

        $this->assertTrue($this->criteria->hasFilters());
    }

    public function testCanGetAllFilters()
    {
        $this->criteria->filterBy('foo:bar');

        $this->assertTrue(is_array($this->criteria->getFilters()));
    }

    public function testCanSortAscending()
    {
        $this->criteria->sortBy('columnName');

        $this->assertEquals('ASC', $this->criteria->getSort('columnName'));
    }

    public function testCanSortDescending()
    {
        $this->criteria->sortBy('columnName', 'DESC');

        $this->assertEquals('DESC', $this->criteria->getSort('columnName'));
    }

    public function testCannotSortByUnknown()
    {
        $this->criteria->sortBy('columnName', 'BOGUS');

        $this->assertEquals('ASC', $this->criteria->getSort('columnName'));
    }

    public function testCanCheckIfCriteriaHasSorting()
    {
        $this->assertFalse($this->criteria->hasSort());

        $this->criteria->sortBy('columnName');

        $this->assertTrue($this->criteria->hasSort());
    }

    public function testCanGetAllSorting()
    {
        $this->criteria->sortBy('columnName');

        $this->assertTrue(is_array($this->criteria->getSort()));
    }
}
