<?php

namespace Gibbon\Domain\Finance;

use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\DataSet;

class FinanceFeeCategoryGateway extends QueryableGateway
{
    use TableAware;
    private static $primaryKey = 'gibbonFinanceFeeCategoryID';
    private static $tableName = 'gibbonFinanceFeeCategory';
    private static $searchableColumns = [];

    public function selectActiveFeeCategories()
    {
        $sql = "SELECT gibbonFinanceFeeCategoryID as value, name FROM gibbonFinanceFeeCategory WHERE active='Y' AND NOT gibbonFinanceFeeCategoryID=1 ORDER BY name";

        return $this->db()->select($sql);
    }
}
