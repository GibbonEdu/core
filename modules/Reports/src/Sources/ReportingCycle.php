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


        $data = ['gibbonReportID' => $ids['gibbonReportID']];
        $sql = "SELECT allCycles.cycleNumber, allCycles.name
                FROM gibbonReport 
                JOIN gibbonReportingCycle AS currentCycle ON (currentCycle.gibbonReportingCycleID=gibbonReport.gibbonReportingCycleID)
                JOIN gibbonYearGroup ON (FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, currentCycle.gibbonYearGroupIDList))
                JOIN gibbonReportingCycle AS allCycles ON (FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, allCycles.gibbonYearGroupIDList))
                WHERE gibbonReport.gibbonReportID=:gibbonReportID
                GROUP BY allCycles.gibbonReportingCycleID
                ORDER BY allCycles.sequenceNumber, allCycles.cycleNumber";

        $values['cycles'] = $this->db()->select($sql, $data)->fetchKeyPair();

        return $values;
    }
}
