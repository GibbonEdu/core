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

    public function queryFinanceCycles(QueryCriteria $criteria)
    {

        $query = $this
            ->newQuery()
            ->from('gibbonFinanceBudgetCycle')
            ->orderBy(
                [
                'sequenceNumber'
                ]
            )
            ->cols(
                [
                'gibbonFinanceBudgetCycleID',
                'name',
                'status',
                'dateStart',
                'dateEnd',
                'sequenceNumber',
                "IF(dateStart > CURRENT_TIMESTAMP(),'Y','N') as inFuture",
                "IF(dateEnd < CURRENT_TIMESTAMP(),'Y','N') as inPast"
                ]
            );

        $criteria->addFilterRules(
            [
            'status' => function ($query, $status) {
                return $query
                ->where('gibbonFinanceBudgetCycle.status = :status')
                ->bindValue('status', $status);
            },
            'inPast' => function ($query, $inPast) {
                return $query
                ->where('inPast = :inPast')
                ->bindValue('inPast', $inPast);
            },
            'inFuture' => function ($query, $inFuture) {
                return $query
                ->where('inFuture = :inFuture')
                ->bindValue('inFuture', $inFuture);
            }
            ]
        );

        return $this->runQuery($query, $criteria);
    }
}
