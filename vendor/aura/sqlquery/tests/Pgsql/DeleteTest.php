<?php
namespace Aura\SqlQuery\Pgsql;

use Aura\SqlQuery\Common;

class DeleteTest extends Common\DeleteTest
{
    protected $db_type = 'pgsql';

    public function testReturning()
    {
        $this->query->from('t1')
                    ->where('foo = :foo', ['foo' => 'bar'])
                    ->where('baz = :baz', ['baz' => 'dib'])
                    ->orWhere('zim = gir')
                    ->returning(array('foo', 'baz', 'zim'));

        $actual = $this->query->__toString();
        $expect = "
            DELETE FROM <<t1>>
            WHERE
                foo = :foo
                AND baz = :baz
                OR zim = gir
            RETURNING
                foo,
                baz,
                zim
        ";
        $this->assertSameSql($expect, $actual);

        $actual = $this->query->getBindValues();
        $expect = array(
            'foo' => 'bar',
            'baz' => 'dib',
        );
        $this->assertSame($expect, $actual);
    }
}
