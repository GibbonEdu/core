<?php
namespace Aura\SqlQuery\Pgsql;

use Aura\SqlQuery\Common;

class UpdateTest extends Common\UpdateTest
{
    protected $db_type = 'pgsql';

    public function testReturning()
    {
        $this->query->table('t1')
                    ->cols(array('c1', 'c2', 'c3'))
                    ->set('c4', null)
                    ->set('c5', 'NOW()')
                    ->where('foo = ?', 'bar')
                    ->where('baz = ?', 'dib')
                    ->orWhere('zim = gir')
                    ->returning(array('c1', 'c2'))
                    ->returning(array('c3'));

        $actual = $this->query->__toString();
        $expect = "
            UPDATE <<t1>>
            SET
                <<c1>> = :c1,
                <<c2>> = :c2,
                <<c3>> = :c3,
                <<c4>> = NULL,
                <<c5>> = NOW()
            WHERE
                foo = :_1_
                AND baz = :_2_
                OR zim = gir
            RETURNING
                c1,
                c2,
                c3
        ";
        $this->assertSameSql($expect, $actual);

        $actual = $this->query->getBindValues();
        $expect = array(
            '_1_' => 'bar',
            '_2_' => 'dib',
        );
        $this->assertSame($expect, $actual);
    }
}
