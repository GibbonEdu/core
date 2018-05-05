<?php
namespace Aura\SqlQuery;

class QueryFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provider
     */
    public function test($db_type, $common, $query_type, $expect)
    {
        $query_factory = new QueryFactory($db_type, $common);
        $method = 'new' . $query_type;
        $actual = $query_factory->$method();
        $this->assertInstanceOf($expect, $actual);
    }

    public function provider()
    {
        return array(
            // db-specific
            array('Common', false, 'Select', 'Aura\SqlQuery\Common\Select'),
            array('Common', false, 'Insert', 'Aura\SqlQuery\Common\Insert'),
            array('Common', false, 'Update', 'Aura\SqlQuery\Common\Update'),
            array('Common', false, 'Delete', 'Aura\SqlQuery\Common\Delete'),
            array('Mysql',  false, 'Select', 'Aura\SqlQuery\Mysql\Select'),
            array('Mysql',  false, 'Insert', 'Aura\SqlQuery\Mysql\Insert'),
            array('Mysql',  false, 'Update', 'Aura\SqlQuery\Mysql\Update'),
            array('Mysql',  false, 'Delete', 'Aura\SqlQuery\Mysql\Delete'),
            array('Pgsql',  false, 'Select', 'Aura\SqlQuery\Pgsql\Select'),
            array('Pgsql',  false, 'Insert', 'Aura\SqlQuery\Pgsql\Insert'),
            array('Pgsql',  false, 'Update', 'Aura\SqlQuery\Pgsql\Update'),
            array('Pgsql',  false, 'Delete', 'Aura\SqlQuery\Pgsql\Delete'),
            array('Sqlite', false, 'Select', 'Aura\SqlQuery\Sqlite\Select'),
            array('Sqlite', false, 'Insert', 'Aura\SqlQuery\Sqlite\Insert'),
            array('Sqlite', false, 'Update', 'Aura\SqlQuery\Sqlite\Update'),
            array('Sqlite', false, 'Delete', 'Aura\SqlQuery\Sqlite\Delete'),
            array('Sqlsrv', false, 'Select', 'Aura\SqlQuery\Sqlsrv\Select'),
            array('Sqlsrv', false, 'Insert', 'Aura\SqlQuery\Sqlsrv\Insert'),
            array('Sqlsrv', false, 'Update', 'Aura\SqlQuery\Sqlsrv\Update'),
            array('Sqlsrv', false, 'Delete', 'Aura\SqlQuery\Sqlsrv\Delete'),

            // force common
            array('Common', QueryFactory::COMMON, 'Select', 'Aura\SqlQuery\Common\Select'),
            array('Common', QueryFactory::COMMON, 'Insert', 'Aura\SqlQuery\Common\Insert'),
            array('Common', QueryFactory::COMMON, 'Update', 'Aura\SqlQuery\Common\Update'),
            array('Common', QueryFactory::COMMON, 'Delete', 'Aura\SqlQuery\Common\Delete'),
            array('Mysql',  QueryFactory::COMMON, 'Select', 'Aura\SqlQuery\Common\Select'),
            array('Mysql',  QueryFactory::COMMON, 'Insert', 'Aura\SqlQuery\Common\Insert'),
            array('Mysql',  QueryFactory::COMMON, 'Update', 'Aura\SqlQuery\Common\Update'),
            array('Mysql',  QueryFactory::COMMON, 'Delete', 'Aura\SqlQuery\Common\Delete'),
            array('Pgsql',  QueryFactory::COMMON, 'Select', 'Aura\SqlQuery\Common\Select'),
            array('Pgsql',  QueryFactory::COMMON, 'Insert', 'Aura\SqlQuery\Common\Insert'),
            array('Pgsql',  QueryFactory::COMMON, 'Update', 'Aura\SqlQuery\Common\Update'),
            array('Pgsql',  QueryFactory::COMMON, 'Delete', 'Aura\SqlQuery\Common\Delete'),
            array('Sqlite', QueryFactory::COMMON, 'Select', 'Aura\SqlQuery\Common\Select'),
            array('Sqlite', QueryFactory::COMMON, 'Insert', 'Aura\SqlQuery\Common\Insert'),
            array('Sqlite', QueryFactory::COMMON, 'Update', 'Aura\SqlQuery\Common\Update'),
            array('Sqlite', QueryFactory::COMMON, 'Delete', 'Aura\SqlQuery\Common\Delete'),
            array('Sqlsrv', QueryFactory::COMMON, 'Select', 'Aura\SqlQuery\Common\Select'),
            array('Sqlsrv', QueryFactory::COMMON, 'Insert', 'Aura\SqlQuery\Common\Insert'),
            array('Sqlsrv', QueryFactory::COMMON, 'Update', 'Aura\SqlQuery\Common\Update'),
            array('Sqlsrv', QueryFactory::COMMON, 'Delete', 'Aura\SqlQuery\Common\Delete'),
        );
    }

    public function testSeqBindPrefix()
    {
        $query_factory = new QueryFactory('sqlite');

        $first = $query_factory->newSelect();
        $this->assertSame('', $first->getSeqBindPrefix());

        $again = $query_factory->newSelect();
        $this->assertSame('_1', $again->getSeqBindPrefix());
    }
}
