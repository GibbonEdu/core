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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

namespace Gibbon\Module\Reports\Sources;

use Gibbon\Module\Reports\DataSource;

class ReportingCycle extends DataSource
{
    public function getSchema()
    {
        return [
            'name'           => ['numerify', 'Sample Report #'],
            'nameShort'      => ['numerify', 'Sample #'],
            'sequenceNumber' => ['randomDigit'],
            'cycleNumber'    => ['numberBetween', 1, 3],
            'cycleTotal'    => '3',
            'dateStart'      => ['date', 'Y-m-d', '+1 month'],
            'dateEnd'        => ['date', 'Y-m-d', '+6 months'],
            'notes'          => ['paragraph'],
            'cycles'         => [
                1 => 'Cycle 1',
                2 => 'Cycle 2',
                3 => 'Cycle 3',
            ],
        ];
    }

    public function getData($ids = [])
    {
        $data = ['gibbonReportID' => $ids['gibbonReportID']];
        $sql = "SELECT gibbonReportingCycle.name, gibbonReportingCycle.nameShort, gibbonReportingCycle.sequenceNumber, gibbonReportingCycle.cycleNumber, gibbonReportingCycle.cycleTotal, gibbonReportingCycle.dateStart, gibbonReportingCycle.dateEnd, gibbonReportingCycle.notes
                FROM gibbonReport 
                JOIN gibbonReportingCycle ON (gibbonReportingCycle.gibbonReportingCycleID=gibbonReport.gibbonReportingCycleID)
                WHERE gibbonReport.gibbonReportID=:gibbonReportID";

        $values = $this->db()->selectOne($sql, $data);


        $data = ['gibbonStudentEnrolmentID' => $ids['gibbonStudentEnrolmentID'], 'gibbonReportID' => $ids['gibbonReportID']];
        $sql = "SELECT allCycles.cycleNumber, allCycles.nameShort
                FROM gibbonReport 
                JOIN gibbonYearGroup ON (FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonReport.gibbonYearGroupIDList))
                JOIN gibbonReportingCycle AS allCycles ON (allCycles.gibbonSchoolYearID=gibbonReport.gibbonSchoolYearID)
                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonReport.gibbonSchoolYearID)
                WHERE gibbonReport.gibbonReportID=:gibbonReportID
                AND gibbonStudentEnrolment.gibbonStudentEnrolmentID=:gibbonStudentEnrolmentID
                AND FIND_IN_SET(gibbonStudentEnrolment.gibbonYearGroupID, allCycles.gibbonYearGroupIDList)
                AND FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, allCycles.gibbonYearGroupIDList)
                GROUP BY allCycles.gibbonReportingCycleID
                ORDER BY allCycles.sequenceNumber, allCycles.cycleNumber";

        $values['cycles'] = $this->db()->select($sql, $data)->fetchKeyPair();

        return $values;
    }
}
