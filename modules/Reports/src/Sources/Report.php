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

namespace Gibbon\Module\Reports\Sources;

use Gibbon\Module\Reports\DataSource;

class Report extends DataSource
{
    public function getSchema()
    {
        return [
            'name'       => "Sample Report",
            'status'     => "Final",
            'date'       => ['date', 'Y-m-d'],
            'schoolYear' => '2019-2020',
            'currentYear' => '2019-2020',
        ];
    }

    public function getData($ids = [])
    {
        $data = ['gibbonReportID' => $ids['gibbonReportID']];
        $sql = "SELECT gibbonReport.name, gibbonReport.status, gibbonReport.accessDate as date, gibbonSchoolYear.name as schoolYear, (SELECT name FROM gibbonSchoolYear WHERE status='Current' LIMIT 1) as currentYear
                FROM gibbonReport 
                JOIN gibbonSchoolYear ON (gibbonSchoolYear.gibbonSchoolYearID=gibbonReport.gibbonSchoolYearID)
                WHERE gibbonReport.gibbonReportID=:gibbonReportID";

        return $this->db()->selectOne($sql, $data);
    }
}
