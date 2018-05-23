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

namespace Gibbon\Domain\School;

use Gibbon\Domain\Gateway;
use Gibbon\Domain\Traits\TableAware;

/**
 * RollGroup Gateway
 *
 * @version v16
 * @since   v16
 */
class RollGroupGateway extends Gateway
{
    use TableAware;

    private static $tableName = 'gibbonRollGroup';
    private static $searchableColumns = [];

    public function selectRollGroupsBySchoolYear($gibbonSchoolYearID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'today' => date('Y-m-d'));
        $sql = "SELECT gibbonRollGroup.gibbonRollGroupID, gibbonRollGroup.name, gibbonRollGroup.nameShort, gibbonSpace.name AS space, gibbonRollGroup.website, gibbonPersonIDTutor, gibbonPersonIDTutor2, gibbonPersonIDTutor3, COUNT(DISTINCT students.gibbonPersonID) as students
                FROM gibbonRollGroup 
                LEFT JOIN (
                    SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolment.gibbonRollGroupID FROM gibbonStudentEnrolment 
                    JOIN gibbonPerson ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                    WHERE status='Full' AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL OR dateEnd>=:today)
                ) AS students ON (students.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                LEFT JOIN gibbonSpace ON (gibbonRollGroup.gibbonSpaceID=gibbonSpace.gibbonSpaceID) 
                WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID 
                GROUP BY gibbonRollGroup.gibbonRollGroupID
                ORDER BY gibbonRollGroup.name";

        return $this->db()->select($sql, $data);
    }

    public function selectTutorsByRollGroup($gibbonRollGroupID)
    {
        $data = array('gibbonRollGroupID' => $gibbonRollGroupID);
        $sql = "SELECT gibbonPersonID, title, surname, preferredName 
                FROM gibbonRollGroup 
                LEFT JOIN gibbonPerson ON (gibbonPersonID=gibbonRollGroup.gibbonPersonIDTutor OR gibbonPersonID=gibbonRollGroup.gibbonPersonIDTutor2 OR gibbonPersonID=gibbonRollGroup.gibbonPersonIDTutor3)
                WHERE gibbonRollGroup.gibbonRollGroupID=:gibbonRollGroupID 
                ORDER BY gibbonPersonID=gibbonRollGroup.gibbonPersonIDTutor DESC, gibbonPersonID=gibbonRollGroup.gibbonPersonIDTutor2 DESC";

        return $this->db()->select($sql, $data);
    }
}
