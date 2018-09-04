<?php
namespace Aura\SqlQuery\Common;

use Aura\SqlQuery\AbstractQueryTest;

class InsertTest extends AbstractQueryTest
{
    protected $query_type = 'insert';

    protected function newQuery()
    {
        $this->query_factory->setLastInsertIdNames(array(
            'tablex.colx' => 'tablex_colx_alternative_name',
        ));
        return parent::newQuery();
    }

    public function testCommon()
    {
        $this->query->into('t1')
                    ->cols(array('c1', 'c2'))
                    ->col('c3')
                    ->set('c4', 'NOW()')
                    ->set('c5', null)
                    ->cols(array('cx' => 'cx_value'));

        $actual = $this->query->__toString();
        $expect = '
            INSERT INTO <<t1>> (
                <<c1>>,
                <<c2>>,
                <<c3>>,
                <<c4>>,
                <<c5>>,
                <<cx>>
            ) VALUES (
                :c1,
                :c2,
                :c3,
                NOW(),
                NULL,
                :cx
            )
        ';

        $this->assertSameSql($expect, $actual);

        $actual = $this->query->getBindValues();
        $expect = array('cx' => 'cx_value');
        $this->assertSame($expect, $actual);
    }

    public function testGetLastInsertIdName_default()
    {
        $this->query->into('table');
        $expect = null;
        $actual = $this->query->getLastInsertIdName('col');
        $this->assertSame($expect, $actual);
    }

    public function testGetLastInsertIdName_alternative()
    {
        $this->query->into('tablex');
        $expect = 'tablex_colx_alternative_name';
        $actual = $this->query->getLastInsertIdName('colx');
        $this->assertSame($expect, $actual);
    }

    public function testBindValues()
    {
        $this->assertInstanceOf('\Aura\SqlQuery\AbstractQuery', $this->query->bindValues(array('bar', 'bar value')));
    }

    public function testBindValue()
    {
        $this->assertInstanceOf('\Aura\SqlQuery\AbstractQuery', $this->query->bindValue('bar', 'bar value'));
    }

    public function testBulkAddRow()
    {
        $this->query->into('t1');

        $this->query->cols(array('c1' => 'v1-0', 'c2' => 'v2-0'));
        $this->query->col('c3', 'v3-0');
        $this->query->set('c4', 'NOW() - 0');

        $this->query->addRow();

        $this->query->col('c3', 'v3-1');
        $this->query->set('c4', 'NOW() - 1');
        $this->query->cols(array('c2' => 'v2-1', 'c1' => 'v1-1'));

        $this->query->addRow();

        $this->query->set('c4', 'NOW() - 2');
        $this->query->col('c1', 'v1-2');
        $this->query->cols(array('c2' => 'v2-2', 'c3' => 'v3-2'));

        $actual = $this->query->__toString();
        $expect = '
            INSERT INTO <<t1>>
                (<<c1>>, <<c2>>, <<c3>>, <<c4>>)
            VALUES
                (:c1_0, :c2_0, :c3_0, NOW() - 0),
                (:c1_1, :c2_1, :c3_1, NOW() - 1),
                (:c1_2, :c2_2, :c3_2, NOW() - 2)
        ';

        $this->assertSameSql($expect, $actual);

        $expect = array (
            'c1_0' => 'v1-0',
            'c2_0' => 'v2-0',
            'c3_0' => 'v3-0',
            'c1_1' => 'v1-1',
            'c2_1' => 'v2-1',
            'c3_1' => 'v3-1',
            'c1_2' => 'v1-2',
            'c2_2' => 'v2-2',
            'c3_2' => 'v3-2',
        );
        $actual = $this->query->getBindValues();
        $this->assertSame($expect, $actual);
    }

    public function testBulkMissingCol()
    {
        $this->query->into('t1');

        // the needed cols
        $this->query->cols(array('c1' => 'v1-0', 'c2' => 'v2-0'));
        $this->query->col('c3', 'v3-0');
        $this->query->set('c4', 'NOW() - 0');

        // add another row
        $this->query->addRow();
        $this->query->set('c4', 'NOW() - 1');
        $this->query->cols(array('c2' => 'v2-1', 'c1' => 'v1-1'));

        // failed to add c3, should blow up

        $this->setExpectedException(
            'Aura\SqlQuery\Exception',
            $this->requoteIdentifiers("Column <<c3>> missing from row 1.")
        );
        $this->query->addRow();
    }

    public function testBulkEmptyRow()
    {
        $this->query->into('t1');

        $this->query->cols(array('c1' => 'v1-0', 'c2' => 'v2-0'));
        $this->query->col('c3', 'v3-0');
        $this->query->set('c4', 'NOW() - 0');

        $this->query->addRow();

        $this->query->col('c3', 'v3-1');
        $this->query->set('c4', 'NOW() - 1');
        $this->query->cols(array('c2' => 'v2-1', 'c1' => 'v1-1'));

        $this->query->addRow();

        $this->query->set('c4', 'NOW() - 2');
        $this->query->col('c1', 'v1-2');
        $this->query->cols(array('c2' => 'v2-2', 'c3' => 'v3-2'));

        // add an empty row
        $this->query->addRow();

        // should be the same as testBulk()
        $actual = $this->query->__toString();
        $expect = '
            INSERT INTO <<t1>>
                (<<c1>>, <<c2>>, <<c3>>, <<c4>>)
            VALUES
                (:c1_0, :c2_0, :c3_0, NOW() - 0),
                (:c1_1, :c2_1, :c3_1, NOW() - 1),
                (:c1_2, :c2_2, :c3_2, NOW() - 2)
        ';

        $this->assertSameSql($expect, $actual);

        $expect = array (
            'c1_0' => 'v1-0',
            'c2_0' => 'v2-0',
            'c3_0' => 'v3-0',
            'c1_1' => 'v1-1',
            'c2_1' => 'v2-1',
            'c3_1' => 'v3-1',
            'c1_2' => 'v1-2',
            'c2_2' => 'v2-2',
            'c3_2' => 'v3-2',
        );
        $actual = $this->query->getBindValues();
        $this->assertSame($expect, $actual);
    }

    public function testBulkAddRows()
    {
        $this->query->into('t1');
        $this->query->addRows(array(
            array(
                'c1' => 'v1-0',
                'c2' => 'v2-0',
                'c3' => 'v3-0',
            ),
            array(
                'c1' => 'v1-1',
                'c2' => 'v2-1',
                'c3' => 'v3-1',
            ),
            array(
                'c1' => 'v1-2',
                'c2' => 'v2-2',
                'c3' => 'v3-2',
            ),
        ));

        $actual = $this->query->__toString();
        $expect = '
            INSERT INTO <<t1>>
                (<<c1>>, <<c2>>, <<c3>>)
            VALUES
                (:c1_0, :c2_0, :c3_0),
                (:c1_1, :c2_1, :c3_1),
                (:c1_2, :c2_2, :c3_2)
        ';

        $this->assertSameSql($expect, $actual);

        $expect = array (
            'c1_0' => 'v1-0',
            'c2_0' => 'v2-0',
            'c3_0' => 'v3-0',
            'c1_1' => 'v1-1',
            'c2_1' => 'v2-1',
            'c3_1' => 'v3-1',
            'c1_2' => 'v1-2',
            'c2_2' => 'v2-2',
            'c3_2' => 'v3-2',
        );
        $actual = $this->query->getBindValues();
        $this->assertSame($expect, $actual);
    }

    public function testIssue60_addRowsWithOnlyOneRow()
    {
        $this->query->into('t1');
        $this->query->addRows(array(
            array(
                'c1' => 'v1-0',
                'c2' => 'v2-0',
                'c3' => 'v3-0',
            ),
        ));

        $actual = $this->query->__toString();
        $expect = '
            INSERT INTO <<t1>> (
                <<c1>>,
                <<c2>>,
                <<c3>>
            ) VALUES (
                :c1,
                :c2,
                :c3
            )
        ';

        $this->assertSameSql($expect, $actual);

        $expect = array (
            'c1' => 'v1-0',
            'c2' => 'v2-0',
            'c3' => 'v3-0',
        );
        $actual = $this->query->getBindValues();
        $this->assertSame($expect, $actual);
    }

    public function testIssue60_repeatedAddRowsWithOnlyOneRow()
    {
        $this->query->into('t1');
        $this->query->addRows(array(
            array(
                'c1' => 'v1-0',
                'c2' => 'v2-0',
                'c3' => 'v3-0',
            ),
        ));

        $this->query->addRows(array(
            array(
                'c1' => 'v1-1',
                'c2' => 'v2-1',
                'c3' => 'v3-1',
            ),
        ));

        $this->query->addRows(array(
            array(
                'c1' => 'v1-2',
                'c2' => 'v2-2',
                'c3' => 'v3-2',
            ),
        ));

        $actual = $this->query->__toString();
        $expect = '
            INSERT INTO <<t1>>
                (<<c1>>, <<c2>>, <<c3>>)
            VALUES
                (:c1_0, :c2_0, :c3_0),
                (:c1_1, :c2_1, :c3_1),
                (:c1_2, :c2_2, :c3_2)
        ';

        $this->assertSameSql($expect, $actual);

        $expect = array (
            'c1_0' => 'v1-0',
            'c2_0' => 'v2-0',
            'c3_0' => 'v3-0',
            'c1_1' => 'v1-1',
            'c2_1' => 'v2-1',
            'c3_1' => 'v3-1',
            'c1_2' => 'v1-2',
            'c2_2' => 'v2-2',
            'c3_2' => 'v3-2',
        );
        $actual = $this->query->getBindValues();
        $this->assertSame($expect, $actual);
    }
}
