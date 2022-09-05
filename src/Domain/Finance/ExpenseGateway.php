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

    public function queryExpenseLogByID(QueryCriteria $criteria, $gibbonFinanceExpenseID)
    {
        $query = $this
        ->newQuery()
        ->cols([
            'gibbonPerson.preferredName',
            'gibbonPerson.title',
            'gibbonPerson.surname',
            'gibbonFinanceExpenseLog.timestamp',
            'gibbonFinanceExpenseLog.comment',
            'gibbonFinanceExpenseLog.action'
          ])
        ->from('gibbonFinanceExpenseLog')
        ->innerJoin('gibbonPerson', 'gibbonFinanceExpenseLog.gibbonPersonID = gibbonPerson.gibbonPersonID')
        ->where('gibbonFinanceExpenseLog.gibbonFinanceExpenseID = :gibbonFinanceExpenseID')
        ->bindValue('gibbonFinanceExpenseID', $gibbonFinanceExpenseID);

        return $this->runQuery($query, $criteria);
    }
}
