<?php
namespace Aura\SqlQuery\Mysql;

use Aura\SqlQuery\Common;

class SelectTest extends Common\SelectTest
{
    protected $db_type = 'mysql';

    protected $expected_sql_with_flag = '
        SELECT %s
            <<t1>>.<<c1>>,
            <<t1>>.<<c2>>,
            <<t1>>.<<c3>>
        FROM
            <<t1>>
    ';

    public function testMultiFlags()
    {
        $this->query->calcFoundRows()
                    ->distinct()
                    ->noCache()
                    ->from('t1')
                    ->cols(array('t1.c1', 't1.c2', 't1.c3'));

        $actual = $this->query->__toString();

        $expect = sprintf($this->expected_sql_with_flag, 'SQL_CALC_FOUND_ROWS DISTINCT SQL_NO_CACHE');
        $this->assertSameSql($expect, $actual);
    }

    public function testCalcFoundRows()
    {
        $this->query->calcFoundRows()
                     ->from('t1')
                     ->cols(array('t1.c1', 't1.c2', 't1.c3'));

        $actual = $this->query->__toString();

        $expect = sprintf($this->expected_sql_with_flag, 'SQL_CALC_FOUND_ROWS');
        $this->assertSameSql($expect, $actual);
    }

    public function testCache()
    {
        $this->query->cache()
                     ->from('t1')
                     ->cols(array('t1.c1', 't1.c2', 't1.c3'));

        $actual = $this->query->__toString();

        $expect = sprintf($this->expected_sql_with_flag, 'SQL_CACHE');
        $this->assertSameSql($expect, $actual);
    }

    public function testNoCache()
    {
        $this->query->noCache()
                     ->from('t1')
                     ->cols(array('t1.c1', 't1.c2', 't1.c3'));

        $actual = $this->query->__toString();

        $expect = sprintf($this->expected_sql_with_flag, 'SQL_NO_CACHE');
        $this->assertSameSql($expect, $actual);
    }

    public function testStraightJoin()
    {
        $this->query->straightJoin()
                     ->from('t1')
                     ->cols(array('t1.c1', 't1.c2', 't1.c3'));

        $actual = $this->query->__toString();

        $expect = sprintf($this->expected_sql_with_flag, 'STRAIGHT_JOIN');
        $this->assertSameSql($expect, $actual);
    }

    public function testHighPriority()
    {
        $this->query->highPriority()
                     ->from('t1')
                     ->cols(array('t1.c1', 't1.c2', 't1.c3'));

        $actual = $this->query->__toString();

        $expect = sprintf($this->expected_sql_with_flag, 'HIGH_PRIORITY');
        $this->assertSameSql($expect, $actual);
    }

    public function testSmallResult()
    {
        $this->query->smallResult()
                     ->from('t1')
                     ->cols(array('t1.c1', 't1.c2', 't1.c3'));

        $actual = $this->query->__toString();

        $expect = sprintf($this->expected_sql_with_flag, 'SQL_SMALL_RESULT');
        $this->assertSameSql($expect, $actual);
    }

    public function testBigResult()
    {
        $this->query->bigResult()
                     ->from('t1')
                     ->cols(array('t1.c1', 't1.c2', 't1.c3'));

        $actual = $this->query->__toString();

        $expect = sprintf($this->expected_sql_with_flag, 'SQL_BIG_RESULT');
        $this->assertSameSql($expect, $actual);
    }

    public function testBufferResult()
    {
        $this->query->bufferResult()
                     ->from('t1')
                     ->cols(array('t1.c1', 't1.c2', 't1.c3'));

        $actual = $this->query->__toString();

        $expect = sprintf($this->expected_sql_with_flag, 'SQL_BUFFER_RESULT');
        $this->assertSameSql($expect, $actual);
    }
}
