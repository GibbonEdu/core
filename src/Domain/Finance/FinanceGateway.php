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
            ->orderBy(
                [
                'gibbonFinanceFee.active',
                'gibbonFinanceFee.name'
                ]
            )
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
}
