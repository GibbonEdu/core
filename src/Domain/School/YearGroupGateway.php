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

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * YearGroup Gateway
 *
 * @version v16
 * @since   v16
 */
class YearGroupGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonYearGroup';
    private static $searchableColumns = [];

    public function queryYearGroups(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonYearGroupID', 'name', 'nameShort', 'sequenceNumber', 'gibbonPersonIDHOY', 'preferredName', 'surname'
            ])
            ->leftJoin('gibbonPerson', 'gibbonYearGroup.gibbonPersonIDHOY=gibbonPerson.gibbonPersonID');

        return $this->runQuery($query, $criteria);
    }

    public function studentCountByYearGroup($gibbonYearGroupID)
    {
        $data = array('gibbonYearGroupID' => $gibbonYearGroupID, 'today' => date('Y-m-d'));
        $sql = "SELECT count(*)
            FROM gibbonStudentEnrolment
                JOIN gibbonPerson ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
            WHERE gibbonPerson.status='Full'
                AND gibbonSchoolYear.status='Current'
                AND (dateStart IS NULL OR dateStart<=:today)
                AND (dateEnd IS NULL OR dateEnd>=:today)
                AND gibbonYearGroupID=:gibbonYearGroupID
                ";

        return $this->db()->selectOne($sql, $data);
    }
}
