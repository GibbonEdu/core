<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/mit-license.php MIT
 *
 */
namespace Aura\SqlQuery\Common;

use Aura\SqlQuery\AbstractQueryTest;

class DeleteTest extends AbstractQueryTest
{
    protected $query_type = 'delete';

    public function testCommon()
    {
        $this->query->from('t1')
                    ->where('foo = :foo', ['foo' => 'bar'])
                    ->where('baz = :baz', ['baz' => 'dib'])
                    ->orWhere('zim = gir');

        $actual = $this->query->__toString();
        $expect = "
            DELETE FROM <<t1>>
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
}
