<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

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
