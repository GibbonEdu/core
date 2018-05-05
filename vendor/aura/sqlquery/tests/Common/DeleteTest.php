<?php
namespace Aura\SqlQuery\Common;

use Aura\SqlQuery\AbstractQueryTest;

class DeleteTest extends AbstractQueryTest
{
    protected $query_type = 'delete';

    public function testCommon()
    {
        $this->query->from('t1')
                    ->where('foo = ?', 'bar')
                    ->where('baz = ?', 'dib')
                    ->orWhere('zim = gir');

        $actual = $this->query->__toString();
        $expect = "
            DELETE FROM <<t1>>
            WHERE
                foo = :_1_
                AND baz = :_2_
                OR zim = gir
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
