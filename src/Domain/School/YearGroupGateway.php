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

use Gibbon\Domain\DataSet;
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
    private static $primaryKey = 'gibbonYearGroupID';
    private static $searchableColumns = [];

    /**
     * Query for the year group.
     *
     * @version v16
     * @since   v16
     *
     * @param QueryCriteria $criteria
     *
     * @return DataSet
     */
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

    /**
     * Get student count by year group.
     *
     * @version v16
     * @since   v16
     *
     * @param int $gibbonYearGroupID
     *
     * @return array|false
     */
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

    /**
     * Select year group by ids.
     *
     * @version v24
     * @since   v24
     *
     * @param int[] $gibbonYearGroupIDList  An array of year group IDs.
     * @return void
     */
    public function selectYearGroupsByIDs($gibbonYearGroupIDList)
    {
        $data = ['gibbonYearGroupIDList' => is_array($gibbonYearGroupIDList) ? implode(',', $gibbonYearGroupIDList) : $gibbonYearGroupIDList];
        $sql = "SELECT gibbonYearGroupID as value, name FROM gibbonYearGroup WHERE FIND_IN_SET(gibbonYearGroupID, :gibbonYearGroupIDList) ORDER BY sequenceNumber";
        return $this->db()->select($sql, $data);
    }

    /**
     * Take a year group, and return the next one, or false if none.
     *
     * @version v25
     * @since   v25
     *
     * @param int $gibbonYearGroupID
     *
     * @return int|false
     */
    public function getNextYearGroupID(int $gibbonYearGroupID)
    {
        $sql = 'SELECT * FROM gibbonYearGroup WHERE gibbonYearGroupID=:gibbonYearGroupID';
        $result = $this->db()->select($sql, [
            'gibbonYearGroupID' => $gibbonYearGroupID,
        ]);
        if ($result->rowCount() == 1) {
            $row = $result->fetch();
            $sqlPrevious = 'SELECT gibbonYearGroupID FROM gibbonYearGroup WHERE sequenceNumber>:sequenceNumber ORDER BY sequenceNumber ASC';
            $resultPrevious = $this->db()->select($sqlPrevious, [
                'sequenceNumber' => $row['sequenceNumber'],
            ]);
            if ($resultPrevious->rowCount() >= 1) {
                return $resultPrevious->fetchColumn();
            }
        }
        return false;
    }

    /**
     * Return the last school year in the school, or false if none.
     *
     * @version v25
     * @since   v25
     *
     * @return int|false
     */
    public function getLastYearGroupID()
    {
        $sql = 'SELECT gibbonYearGroupID FROM gibbonYearGroup ORDER BY sequenceNumber DESC';
        $result = $this->db()->select($sql);
        if ($result->rowCount() > 1) {
            return $result->fetchColumn();
        }
        return false;
    }
}
