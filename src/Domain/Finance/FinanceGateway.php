<?php

namespace Gibbon\Domain\Finance;

use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\DataSet;

class FinanceGateway extends QueryableGateway
{
    use TableAware;
    private static $primaryKey = 'gibbonFinanceBudgetID';
    private static $tableName = 'gibbonFinanceBudget';
    private static $searchableColumns = [];

    public function queryFinanceBudget(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->orderBy(
                [
                'active DESC'
                ]
            )
            ->cols(
                [
                'name',
                'nameShort',
                'category',
                'active'
                ]
            );

        $criteria->addFilterRules(
            [
            'active' => function ($query,$active) {
                return $query
                    ->where('gibbonFinanceBudget.active = :active')
                    ->bindValue('active', $active);
            }
            ]
        );

        return $this->runQuery($query, $criteria);
    }
}

?>
