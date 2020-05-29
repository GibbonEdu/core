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

    public function queryExpenseApprovers(QueryCriteria $criteria)
    {
        $query = $this
        ->newQuery()
        ->from('gibbonFinanceExpenseApprover')
        ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID = gibbonFinanceExpenseApprover.gibbonPersonID')
        ->where("gibbonPerson.status = 'Full'")
        ->cols([
          'gibbonPerson.title',
          'gibbonPerson.preferredName',
          'gibbonPerson.surname',
          'gibbonFinanceExpenseApprover.sequenceNumber',
          'gibbonFinanceExpenseApprover.gibbonFinanceExpenseApproverID'
        ]);
        return $this->runQuery($query, $criteria);
    }
}
