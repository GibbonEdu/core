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

namespace Gibbon\Domain\School;

use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\DataSet;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\Traits\TableAware;

/**
 * School Year Gateway
 *
 * @version v17
 * @since   v17
 */
class SchoolYearGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonSchoolYear';
    private static $primaryKey = 'gibbonSchoolYearID';

    /**
     * Query for school years.
     *
     * @version v17
     * @since   v17
     *
     * @param QueryCriteria $criteria
     *
     * @return DataSet
     */
    public function querySchoolYears(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonSchoolYearID', 'name', 'sequenceNumber', 'status', 'firstDay', 'lastDay'
            ]);

        return $this->runQuery($query, $criteria);
    }

    /**
     * Get a key value array with gibbonSchoolYearID as keys
     * and the school year name as the values.
     *
     * @version v17
     * @since   v17
     *
     * @param QueryCriteria $criteria
     *
     * @return array
     */
    public function getSchoolYearList($activeOnly = false)
    {
        $sql = "SELECT gibbonSchoolYearID AS value, name FROM gibbonSchoolYear ";
        if ($activeOnly) $sql .= "WHERE (status='Current' OR status='Upcoming') ";
        $sql .= "ORDER BY sequenceNumber";

        return $this->db()->select($sql)->fetchKeyPair();
    }

    /**
     * Get a key value array with gibbonSchoolYearID as keys
     * and the school year name as the values of the specified
     * school years.
     *
     * @version v17
     * @since   v17
     *
     * @param array $schoolYearList  An array of school year IDs
     *
     * @return array
     */
    public function getSchoolYearsFromList($schoolYearList = [])
    {
        $data = ['gibbonSchoolYearIDList' => is_array($schoolYearList) ? implode(',', $schoolYearList) : $schoolYearList];
        $sql = "SELECT gibbonSchoolYearID as value, name FROM gibbonSchoolYear WHERE FIND_IN_SET(gibbonSchoolYearID, :gibbonSchoolYearIDList) ORDER BY sequenceNumber";

        return $this->db()->select($sql, $data)->fetchKeyPair();
    }

    /**
     * Get a single school year by its ID.
     *
     * @version v17
     * @since   v17
     *
     * @param int $gibbonSchoolYearID
     *
     * @return array|false  The information of the spcified school year, or false if not found.
     */
    public function getSchoolYearByID($gibbonSchoolYearID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID";

        return $this->db()->selectOne($sql, $data);
    }

    /**
     * Get the school year next to the specified school year.
     *
     * @version v17
     * @since   v17
     *
     * @param int $gibbonSchoolYearID  The ID of the specified school year.
     *
     * @return array|false  The information of the next school year, or false if not found.
     */
    public function getNextSchoolYearByID($gibbonSchoolYearID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT * FROM gibbonSchoolYear WHERE sequenceNumber=(SELECT MIN(sequenceNumber) FROM gibbonSchoolYear WHERE sequenceNumber > (SELECT sequenceNumber FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID))";

        return $this->db()->selectOne($sql, $data);
    }

    /**
     * Get the school year previous of the specified school year.
     *
     * @version v17
     * @since   v17
     *
     * @param int $gibbonSchoolYearID  The ID of the specified school year.
     *
     * @return array|false  The information of the previous school year, or false if not found.
     */
    public function getPreviousSchoolYearByID($gibbonSchoolYearID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT * FROM gibbonSchoolYear WHERE sequenceNumber=(SELECT MAX(sequenceNumber) FROM gibbonSchoolYear WHERE sequenceNumber < (SELECT sequenceNumber FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID))";

        return $this->db()->selectOne($sql, $data);
    }

    /**
     * Get the current school year information.
     *
     * @version v25
     * @since   v25

     * @return array
     */
    public function getCurrentSchoolYear()
    {
        return $this->db()->selectOne("SELECT * FROM gibbonSchoolYear WHERE status='Current'");
    }
}
