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

namespace Gibbon\Module\Reports\Contexts;

use Gibbon\Services\Format;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Module\Reports\DataContext;

class StudentEnrolmentContext implements DataContext
{
    public function getFormatter()
    {
        return function ($values) {
            return Format::nameLinked($values['gibbonPersonID'], '', $values['preferredName'], $values['surname'], 'Student', true, false, ['subpage' => 'Reports']);
        };
    }

    public function getIdentifiers(Connection $db, string $gibbonReportID, string $gibbonYearGroupID)
    {
        $data = ['gibbonReportID' => $gibbonReportID, 'gibbonYearGroupID' => $gibbonYearGroupID];
        $sql = "SELECT gibbonStudentEnrolmentID, gibbonPerson.gibbonPersonID, gibbonPerson.preferredName, gibbonPerson.surname, gibbonRollGroup.nameShort as rollGroup
                FROM gibbonReport
                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonReport.gibbonSchoolYearID)
                JOIN gibbonYearGroup ON (gibbonYearGroup.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID)
                JOIN gibbonRollGroup ON (gibbonRollGroup.gibbonRollGroupID=gibbonStudentEnrolment.gibbonRollGroupID)
                JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                WHERE gibbonReport.gibbonReportID=:gibbonReportID 
                AND FIND_IN_SET(gibbonStudentEnrolment.gibbonYearGroupID, :gibbonYearGroupID)
                ORDER BY gibbonYearGroup.sequenceNumber, gibbonStudentEnrolment.rollOrder, gibbonPerson.surname, gibbonPerson.preferredName";

        return $db->select($sql, $data)->fetchAll();
    }
}
