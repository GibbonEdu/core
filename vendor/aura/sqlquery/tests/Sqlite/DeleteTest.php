<?php
namespace Aura\SqlQuery\Sqlite;

use Aura\SqlQuery\Common;

class DeleteTest extends Common\DeleteTest
{
    protected $db_type = 'sqlite';

    public function testOrderLimit()
    {
        $this->query->from('t1')
                    ->where('foo = ?', 'bar')
                    ->where('baz = ?', 'dib')
                    ->orWhere('zim = gir')
                    ->orderBy(array('zim DESC'))
                    ->limit(5)
                    ->offset(10);

        $actual = $this->query->__toString();
        $expect = "
            DELETE FROM <<t1>>
            WHERE
                foo = :_1_
                AND baz = :_2_
                OR zim = gir
            ORDER BY
                zim DESC
            LIMIT 5 OFFSET 10
        ";
        $this->assertSameSql($expect, $actual);

        $actual = $this->query->getBindValues();
        $expect = array(
            '_1_' => 'bar',
            '_2_' => 'dib',
        );
        $this->assertSame($expect, $actual);
    }

    public function testGetterOnLimitAndOffset()
    {
        $this->query->from('t1')
                    ->limit(5)
                    ->offset(10);

        $this->assertSame(5, $this->query->getLimit());
        $this->assertSame(10, $this->query->getOffset());
    }
}
