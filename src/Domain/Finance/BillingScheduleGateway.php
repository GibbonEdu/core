<?php

namespace Gibbon\Domain\Finance;

use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\DataSet;

class BillingScheduleGateway extends QueryableGateway
{
    use TableAware;
    private static $primaryKey = 'gibbonFinanceBillingScheduleID';
    private static $tableName = 'gibbonFinanceBillingSchedule';
    private static $searchableColumns = [];

    public function queryBillingSchedules(QueryCriteria $criteria, $gibbonSchoolYearID = null, $search = null)
    {
        $query = $this
        ->newQuery()
        ->cols([
            '*'
          ])
        ->from('gibbonFinanceBillingSchedule');

        if (!empty($gibbonSchoolYearID)) {
            $query->where('gibbonFinanceBillingSchedule.gibbonSchoolYearID=:gibbonSchoolYearID')
                ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);
        }

        if (!empty($search)) {
            $query->where("gibbonFinanceBillingSchedule.name LIKE concat('%',:name,'%')")
                ->bindValue('name', $search);
        }

        return $this->runQuery($query, $criteria);
    }
}
