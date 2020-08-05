<?php

namespace Gibbon\Domain\Finance;

use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\DataSet;

class ExpenseGateway extends QueryableGateway
{
    use TableAware;
    private static $primaryKey = 'gibbonFinanceExpenseID';
    private static $tableName = 'gibbonFinanceExpense';
    private static $searchableColumns = [];

    public function queryExpenses(QueryCriteria $criteria)
    {
        $query = $this
        ->newQuery()
        ->from('gibbonFinanceExpenseLog')
        ->innerJoin('gibbonPerson', 'gibbonFinanceExpenseLog.gibbonPersonID = gibbonPerson.gibbonPersonID')
        ->cols([
          'gibbonPerson.preferredName',
          'gibbonPerson.title',
          'gibbonPerson.surname',
          'gibbonFinanceExpenseLog.timestamp',
          'gibbonFinanceExpenseLog.comment',
          'gibbonFinanceExpenseLog.action'
        ]);

        $criteria->addFilterRules([
        'gibbonFinanceExpenseID' => function ($query, $gibbonFinanceExpenseID) {
            return $query
            ->where('gibbonFinanceExpenseLog.gibbonFinanceExpenseID = :gibbonFinanceExpenseID')
            ->bindValue('gibbonFinanceExpenseID', $gibbonFinanceExpenseID);
        }
        ]);

        return $this->runQuery($query, $criteria);
    }
}
