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

namespace Gibbon\Domain\Timetable;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * @version v25
 * @since   v16
 */
class TimetableGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonTT';
    private static $primaryKey = 'gibbonTTID';

    public function selectTimetablesBySchoolYear($gibbonSchoolYearID) 
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT gibbonTTID, gibbonTT.gibbonSchoolYearID, gibbonTT.name, gibbonTT.nameShort, gibbonTT.active, GROUP_CONCAT(gibbonYearGroup.nameShort ORDER BY gibbonYearGroup.sequenceNumber SEPARATOR ', ') as yearGroups
                FROM gibbonTT 
                LEFT JOIN gibbonYearGroup ON (FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonTT.gibbonYearGroupIDList))
                WHERE gibbonTT.gibbonSchoolYearID=:gibbonSchoolYearID 
                GROUP BY gibbonTT.gibbonTTID
                ORDER BY gibbonTT.name";

        return $this->db()->select($sql, $data);
    }

    public function selectClassesByTimetable($gibbonTTID)
    {
        $data = ['gibbonTTID' => $gibbonTTID];
        $sql = "SELECT gibbonCourseClass.gibbonCourseClassID AS value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) AS name 
                FROM gibbonTT
                JOIN gibbonCourse ON (gibbonCourse.gibbonSchoolYearID=gibbonTT.gibbonSchoolYearID)
                JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                JOIN gibbonYearGroup ON (FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonCourse.gibbonYearGroupIDList))
                WHERE gibbonTT.gibbonTTID=:gibbonTTID
                AND FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonTT.gibbonYearGroupIDList)
                GROUP BY gibbonCourseClass.gibbonCourseClassID
                ORDER BY name";

        return $this->db()->select($sql, $data);
    }

    public function getNonTimetabledYearGroups($gibbonSchoolYearID, $gibbonTTID = null)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonTTID' => $gibbonTTID);
        $sql = "SELECT gibbonYearGroup.gibbonYearGroupID, gibbonYearGroup.name
                FROM gibbonYearGroup
                LEFT JOIN gibbonTT ON (FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonTT.gibbonYearGroupIDList) AND gibbonTT.gibbonSchoolYearID=:gibbonSchoolYearID AND (gibbonTT.active='Y' OR gibbonTT.gibbonTTID=:gibbonTTID))
                WHERE gibbonTT.gibbonTTID IS NULL OR gibbonTT.gibbonTTID=:gibbonTTID
                ORDER BY gibbonYearGroup.sequenceNumber";

        return $this->db()->select($sql, $data)->fetchKeyPair();
    }

    public function getTTByID($gibbonTTID)
    {
        $data = array('gibbonTTID' => $gibbonTTID);
        $sql = "SELECT gibbonTT.gibbonTTID, gibbonTT.name, gibbonTT.nameShort, gibbonTT.nameShortDisplay, gibbonTT.active, gibbonTT.gibbonYearGroupIDList, gibbonSchoolYear.name as schoolYear
                FROM gibbonTT 
                JOIN gibbonSchoolYear ON (gibbonSchoolYear.gibbonSchoolYearID=gibbonTT.gibbonSchoolYearID)
                WHERE gibbonTT.gibbonTTID=:gibbonTTID";

        return $this->db()->selectOne($sql, $data);
    }
}
