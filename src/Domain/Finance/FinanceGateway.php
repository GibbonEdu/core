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

  public function queryFees(QueryCriteria $criteria)
    {

        $query = $this
            ->newQuery()
            ->from('gibbonFinanceFee')
            ->join('left', 'gibbonFinanceFeeCategory', 'gibbonFinanceFee.gibbonFinanceFeeCategoryID = gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID')
            ->cols(
                [
                'gibbonFinanceFee.name',
                'gibbonFinanceFee.nameShort',
                'gibbonFinanceFeeCategory.name as category',
                'gibbonFinanceFee.fee',
                'gibbonFinanceFee.gibbonFinanceFeeID',
                'gibbonFinanceFee.gibbonSchoolYearID',
                'gibbonFinanceFee.active',
                'gibbonFinanceFee.description'
                ]
          );
    
          $criteria->addFilterRules(
            [
               'status' => function ($query, $status) {
                return $query
                ->where('gibbonFinanceFee.active = :status')
                ->bindValue('status', $status);
            },
            'search' => function ($query, $search) {
                return $query
                ->where('(gibbonFinanceFee.name LIKE :search OR gibbonFinanceFeeCategory.name LIKE :search)')
                ->bindValue('search', '%'.$search.'%');
            },
            'gibbonSchoolYearID' => function ($query, $gibbonSchoolYearID) {
                return $query
                ->where('gibbonFinanceFee.gibbonSchoolYearID = :gibbonSchoolYearID')
                ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);
            }
            ]
        );
    
      return $this->runQuery($query, $criteria);
    }  
    
                  
   public function queryFinanceBudget(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols(
                [
                'gibbonFinanceBudget.gibbonFinanceBudgetID',
                'gibbonFinanceBudget.name',
                'gibbonFinanceBudget.nameShort',
                'gibbonFinanceBudget.category',
                'gibbonFinanceBudget.active'
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

    public function queryFinanceCycles(QueryCriteria $criteria)
    {

        $query = $this
            ->newQuery()
            ->from('gibbonFinanceBudgetCycle')
            ->cols(
                [
                'gibbonFinanceBudgetCycle.gibbonFinanceBudgetCycleID',
                'gibbonFinanceBudgetCycle.name',
                'gibbonFinanceBudgetCycle.status',
                'gibbonFinanceBudgetCycle.dateStart',
                'gibbonFinanceBudgetCycle.dateEnd',
                'gibbonFinanceBudgetCycle.sequenceNumber',
                "IF(gibbonFinanceBudgetCycle.dateStart > CURRENT_TIMESTAMP(),'Y','N') as inFuture",
                "IF(gibbonFinanceBudgetCycle.dateEnd < CURRENT_TIMESTAMP(),'Y','N') as inPast"
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

    public function queryExpenseApprovers(QueryCriteria $criteria)
    {
        $query = $this
        ->newQuery()
        ->cols([
          'gibbonPerson.title',
          'gibbonPerson.preferredName',
          'gibbonPerson.surname',
          'gibbonFinanceExpenseApprover.sequenceNumber',
          'gibbonFinanceExpenseApprover.gibbonFinanceExpenseApproverID'
        ])
        ->from('gibbonFinanceExpenseApprover')
        ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID = gibbonFinanceExpenseApprover.gibbonPersonID')
        ->where("gibbonPerson.status = 'Full'");
        
        return $this->runQuery($query, $criteria);
    }
}
