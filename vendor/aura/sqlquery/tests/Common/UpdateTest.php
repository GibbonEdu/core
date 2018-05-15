<?php
namespace Aura\SqlQuery\Common;

use Aura\SqlQuery\AbstractQueryTest;

class UpdateTest extends AbstractQueryTest
{
    protected $query_type = 'update';

    public function testCommon()
    {
        $this->query->table('t1')
                    ->cols(['c1', 'c2'])
                    ->col('c3')
                    ->set('c4', null)
                    ->set('c5', 'NOW()')
                    ->where('foo = :foo', ['foo' => 'bar'])
                    ->where('baz = :baz', ['baz' => 'dib'])
                    ->orWhere('zim = gir');

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
                foo = :foo
                AND baz = :baz
                OR zim = gir
        ";

        $this->assertSameSql($expect, $actual);

        $actual = $this->query->getBindValues();
        $expect = array(
            'foo' => 'bar',
            'baz' => 'dib',
        );
        $this->assertSame($expect, $actual);
    }

    public function testHasCols()
    {
        $this->query->table('t1');
        $this->assertFalse($this->query->hasCols());
        $this->query->cols(['c1', 'c2']);
        $this->assertTrue($this->query->hasCols());
    }
}
