<?php
namespace Aura\SqlQuery\Pgsql;

use Aura\SqlQuery\Common;

class InsertTest extends Common\InsertTest
{
    protected $db_type = 'pgsql';

    public function testReturning()
    {
        $this->query->into('t1')
                    ->cols(array('c1', 'c2', 'c3'))
                    ->set('c4', 'NOW()')
                    ->set('c5', null)
                    ->returning(array('c1', 'c2'))
                    ->returning(array('c3'));

        $actual = $this->query->__toString();
        $expect = "
            INSERT INTO <<t1>> (
                <<c1>>,
                <<c2>>,
                <<c3>>,
                <<c4>>,
                <<c5>>
            ) VALUES (
                :c1,
                :c2,
                :c3,
                NOW(),
                NULL
            )
            RETURNING
                c1,
                c2,
                c3
        ";

        $this->assertSameSql($expect, $actual);
    }

    public function testGetLastInsertIdName_default()
    {
        $this->query->into('table');
        $actual = $this->query->getLastInsertIdName('col');
        $expect = 'table_col_seq';
        $this->assertSame($expect, $actual);
    }
}
