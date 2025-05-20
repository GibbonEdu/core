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

    public function queryExpensesByBudgetCycleID(QueryCriteria $criteria, $gibbonFinanceBudgetCycleID, $gibbonPersonID = null)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonFinanceExpense.gibbonFinanceExpenseID',
                'gibbonFinanceExpense.gibbonPersonIDCreator',
                'gibbonFinanceExpense.status',
                'gibbonFinanceExpense.title',
                'gibbonFinanceExpense.paymentReimbursementStatus',
                'gibbonFinanceExpense.timestampCreator',
                'gibbonFinanceExpense.cost',
                'gibbonFinanceExpense.purchaseBy',
                'gibbonFinanceBudget.name AS budget',
                'gibbonPerson.preferredName',
                'gibbonPerson.surname',
                "FIND_IN_SET(gibbonFinanceExpense.status, 'Pending,Issued,Paid,Refunded,Cancelled') AS defaultSortOrder"
            ])
            ->innerJoin('gibbonFinanceBudget', 'gibbonFinanceExpense.gibbonFinanceBudgetID = gibbonFinanceBudget.gibbonFinanceBudgetID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonFinanceExpense.gibbonPersonIDCreator')
            ->where('gibbonFinanceExpense.gibbonFinanceBudgetCycleID = :gibbonFinanceBudgetCycleID')
            ->bindValue('gibbonFinanceBudgetCycleID', $gibbonFinanceBudgetCycleID);

        if (!empty($gibbonPersonID)) {
            $query->where('gibbonFinanceExpense.gibbonPersonIDCreator = :gibbonPersonID')
                ->bindValue('gibbonPersonID', $gibbonPersonID);
        }

        $criteria->addFilterRules([
            'budget' => function ($query, $gibbonFinanceBudgetID) {
                return $query
                    ->where('gibbonFinanceExpense.gibbonFinanceBudgetID = :gibbonFinanceBudgetID')
                    ->bindValue('gibbonFinanceBudgetID', $gibbonFinanceBudgetID);
            },
            'status' => function ($query, $status) {
                return $query
                    ->where('gibbonFinanceExpense.status = :status')
                    ->bindValue('status', $status);
            },
            'creator' => function ($query, $gibbonPersonIDCreator) {
                return $query
                    ->where('gibbonFinanceExpense.gibbonPersonIDCreator = :gibbonPersonIDCreator')
                    ->bindValue('gibbonPersonIDCreator', $gibbonPersonIDCreator);
            }
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function getExpenseByBudgetID($gibbonFinanceBudgetCycleID, $gibbonFinanceExpenseID)
    {
        $data = ['gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID, 'gibbonFinanceExpenseID' => $gibbonFinanceExpenseID];
        $sql = "SELECT gibbonFinanceExpense.*, gibbonFinanceBudget.name AS budget, surname, preferredName, 'Full' AS access
				FROM gibbonFinanceExpense
				JOIN gibbonFinanceBudget ON (gibbonFinanceExpense.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID)
				JOIN gibbonPerson ON (gibbonFinanceExpense.gibbonPersonIDCreator=gibbonPerson.gibbonPersonID)
				WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceExpenseID=:gibbonFinanceExpenseID AND gibbonFinanceExpense.status='Approved'";

        return $this->db()->selectOne($sql, $data);
    }
}
