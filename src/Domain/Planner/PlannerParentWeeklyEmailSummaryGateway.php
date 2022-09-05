<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

namespace Gibbon\Domain\Planner;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * PlannerParentWeeklyEmailSummary Gateway
 *
 * @version v21
 * @since   v21
 */
class PlannerParentWeeklyEmailSummaryGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonPlannerParentWeeklyEmailSummary';
    private static $primaryKey = 'gibbonPlannerParentWeeklyEmailSummaryID';
    private static $searchableColumns = [];


    public function getAnySummaryDetailsByKey($key)
    {
        $data = ['key' => $key];
        $sql = 'SELECT * FROM gibbonPlannerParentWeeklyEmailSummary WHERE gibbonPlannerParentWeeklyEmailSummary.key=:key';

        return $this->db()->selectOne($sql, $data);
    }

    public function getWeeklySummaryDetailsByParent($gibbonSchoolYearID, $gibbonPersonIDParent, $gibbonPersonIDStudent)
    {
        $data = [
            'gibbonSchoolYearID' => $gibbonSchoolYearID,
            'gibbonPersonIDStudent' => $gibbonPersonIDStudent,
            'gibbonPersonIDParent' => $gibbonPersonIDParent,
            'weekOfYear' => date('W')
        ];
        $sql = 'SELECT * FROM gibbonPlannerParentWeeklyEmailSummary WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonIDStudent=:gibbonPersonIDStudent AND gibbonPersonIDParent=:gibbonPersonIDParent AND weekOfYear=:weekOfYear';

        return $this->db()->selectOne($sql, $data);
    }
}
