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

    public function setUp(): void
    {
        $this->criteria = new QueryCriteria();
    }

    public function testCanBeCreated()
    {
        $this->assertInstanceOf(QueryCriteria::class, $this->criteria);
    }

    public function testCanLoadFromArray()
    {
        $values = array(
            'page' => 5,
            'pageSize' => 42,
            'searchBy' => array('columns' => ['columnName'], 'text' => 'foo bar'),
            'filterBy' => array('foo' => 'bar'),
            'sortBy' => array('columnName' => 'DESC'),
        );

        $this->assertEquals($values, $this->criteria->fromArray($values)->toArray());
    }

    public function testCanSanitizeArray()
    {
        $values = array(
            'page' => "foo",
            'pageSize' => ["thing"],
            'searchBy' => array('columns' => ['columnName'], 'text' => 'foo bar'),
            'filterBy' => array('foo' => 'bar'),
            'sortBy' => array('columnName' => 'DESC'),
            'otherStuff' => [],
        );

        $this->assertNotEquals($values, $this->criteria->fromArray($values)->toArray());
    }

    public function testCanLoadFromJson()
    {
        $values = '{"page":3,"pageSize":24,"searchBy":{"columns":["columnName"],"text":"foo bar"},"filterBy":{"foo":"bar"},"sortBy":{"foo":"DESC"}}';

        $this->assertEquals($values, $this->criteria->fromJson($values)->toJson());
    }

    public function testCanSetPageNumber()
    {
        $this->criteria->page(5);

        $this->assertEquals(5, $this->criteria->getPage());
    }

    public function testCannotSetNegativePageNumber()
    {
        $this->criteria->page(-3);

        $this->assertEquals(1, $this->criteria->getPage());
    }

    public function testCanSetPageSize()
    {
        $this->criteria->pageSize(42);

        $this->assertEquals(42, $this->criteria->getPageSize());
    }   

    public function testCannotSetNegativePageSize()
    {
        $this->criteria->pageSize(-42);

        $this->assertEquals(0, $this->criteria->getPageSize());
    }

    public function testCanSearchByOneColumn()
    {
        $this->criteria->searchBy('columnName', 'foo bar');

        $this->assertTrue($this->criteria->hasSearchColumn('columnName'));
        $this->assertTrue($this->criteria->hasSearchText());
    }

    public function testCanSearchByManyColumns()
    {
        $this->criteria->searchBy(['column1', 'column2', 'column3'], 'foo bar');

        $this->assertTrue($this->criteria->hasSearchColumn('column1'));
        $this->assertTrue($this->criteria->hasSearchColumn('column2'));
        $this->assertTrue($this->criteria->hasSearchColumn('column3'));
    }

    public function testCanModifySearchColumns()
    {
        $this->criteria->searchBy('columnName', 'foo bar');
        $this->criteria->searchBy('otherColumn', 'foo bar');

        $this->assertFalse($this->criteria->hasSearchColumn('columnName'));
        $this->assertTrue($this->criteria->hasSearchColumn('otherColumn'));
    }

    public function testCanCheckIfCriteriaHasSearchText()
    {
        $this->assertFalse($this->criteria->hasSearchText());

        $this->criteria->searchBy('columnName', 'foo bar');

        $this->assertTrue($this->criteria->hasSearchText());
    }

    public function testCanGetAllSearches()
    {
        $this->criteria->searchBy('columnName', 'foo bar');

        $this->assertTrue(is_array($this->criteria->getSearchBy()));
    }

    public function testCanAddFilterToSearchString()
    {
        $this->criteria->searchBy('columnName', 'foo bar active:Y');

        $this->assertTrue($this->criteria->hasFilter('active', 'Y'));
        if (method_exists($this, 'assertStringNotContainsString')) {
            // for newer phpunit versions
            $this->assertStringNotContainsString('active:Y', $this->criteria->getSearchText());
        } else {
            // for older phpunit versions
            $this->assertNotContains('active:Y', $this->criteria->getSearchText());
        }
    }

    public function testCanFilterByString()
    {
        $this->criteria->filterBy('foo:bar');

        $this->assertTrue($this->criteria->hasFilter('foo', 'bar'));
    }

    public function testCanFilterByQuotedString()
    {
        $this->criteria->filterBy('foo:"bar baz"');

        $this->assertTrue($this->criteria->hasFilter('foo', 'bar baz'));
    }

    public function testCanFilterByNameValuePair()
    {
        $this->criteria->filterBy('foo', 'bar');

        $this->assertTrue($this->criteria->hasFilter('foo', 'bar'));
    }

    public function testCanReplaceFilterByName()
    {
        $this->criteria->filterBy('foo', 'bar');
        $this->criteria->filterBy('foo', 'fuzz');

        $this->assertTrue($this->criteria->hasFilter('foo', 'fuzz'));
    }

    public function testCanCheckIfCriteriaHasFilters()
    {
        $this->assertFalse($this->criteria->hasFilter());

        $this->criteria->filterBy('foo:bar');

        $this->assertTrue($this->criteria->hasFilter());
    }
    
    public function testCanGetAllFilters()
    {
        $this->criteria->filterBy('foo:bar');

        $this->assertTrue(is_array($this->criteria->getFilterBy()));
    }

    public function testCanGetAllFiltersAsString()
    {
        $this->criteria->filterBy('foo:bar');
        $this->criteria->filterBy('baz:"some thing"');

        $this->assertEquals('foo:bar baz:"some thing"', $this->criteria->getFilterString());
    }

    public function testCanSortAscending()
    {
        $this->criteria->sortBy('columnName');

        $this->assertEquals('ASC', $this->criteria->getSortBy('columnName'));
    }

    public function testCanSortDescending()
    {
        $this->criteria->sortBy('columnName', 'DESC');

        $this->assertEquals('DESC', $this->criteria->getSortBy('columnName'));
    }

    public function testCannotSortByUnknown()
    {
        $this->criteria->sortBy('columnName', 'BOGUS');

        $this->assertEquals('ASC', $this->criteria->getSortBy('columnName'));
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

        $this->assertTrue(is_array($this->criteria->getSortBy()));
    }

    public function testCanAddFilterRule()
    {
        $this->criteria->addFilterRule('foo', function ($query) { return $query; });

        $this->assertTrue($this->criteria->hasFilterRule('foo'));
    }

    public function testCanAddMultipleFilterRules()
    {
        $this->criteria->addFilterRules([
            'foo' => function ($query) { return $query; },
            'bar' => function ($query) { return $query; },
        ]);

        $this->assertTrue($this->criteria->hasFilterRule('foo'));
        $this->assertTrue($this->criteria->hasFilterRule('bar'));
    }

    public function testCanGetFilterRuleByName()
    {
        $closure = function ($query) { return $query; };

        $this->criteria->addFilterRule('foo', $closure);

        $this->assertEquals($closure, $this->criteria->getFilterRule('foo'));
    }

    public function testWillRemoveSpecialCharactersFromSearchColumns()
    {
        $this->criteria->searchBy('c&o+lu^mnN`ame`;', 'foo bar');
        $this->assertTrue($this->criteria->hasSearchColumn('columnName'));

        $this->criteria->searchBy(['c(o;l`umn`1)', 'co+lu"mn2"'], 'foo bar');
        $this->assertTrue($this->criteria->hasSearchColumn('column1'));
    }

    public function testWillRemoveSpecialCharactersFromFilterNames()
    {
        $this->criteria->filterBy('f&+o^`o`;', 'bar');
        $this->assertTrue($this->criteria->hasFilter('foo', 'bar'));
    }

    public function testWillRemoveSpecialCharactersFromSortColumns()
    {
        $this->criteria->sortBy('f"&+o^`o`";', 'bar');
        $this->assertTrue($this->criteria->hasSort('foo'));
    }
}
