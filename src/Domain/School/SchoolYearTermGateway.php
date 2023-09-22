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

use Gibbon\Contracts\Database\Result;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * School Year Term Gateway
 *
 * @version v25
 * @since   v17
 */
class SchoolYearTermGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonSchoolYearTerm';
    private static $primaryKey = 'gibbonSchoolYearTermID';

    public function querySchoolYearTerms(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonSchoolYearTerm.gibbonSchoolYearTermID',
                'gibbonSchoolYear.gibbonSchoolYearID',
                'gibbonSchoolYearTerm.name',
                'gibbonSchoolYearTerm.nameShort',
                'gibbonSchoolYearTerm.sequenceNumber',
                'gibbonSchoolYear.sequenceNumber AS schoolYearSequence',
                'gibbonSchoolYearTerm.firstDay',
                'gibbonSchoolYearTerm.lastDay',
                'gibbonSchoolYear.name AS schoolYearName',
                "(CASE WHEN NOW() BETWEEN gibbonSchoolYearTerm.firstDay AND gibbonSchoolYearTerm.lastDay THEN 'Current' ELSE '' END) as status"
            ])
            ->innerJoin('gibbonSchoolYear', 'gibbonSchoolYear.gibbonSchoolYearID=gibbonSchoolYearTerm.gibbonSchoolYearID');

        $criteria->addFilterRules([
            'schoolYear' => function ($query, $gibbonSchoolYearID) {
                return $query
                    ->where('gibbonSchoolYearTerm.gibbonSchoolYearID=:gibbonSchoolYearID')
                    ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);
            },
            'firstDay' => function ($query, $firstDay) {
                return $query
                    ->where('gibbonSchoolYearTerm.firstDay <= :firstDay')
                    ->bindValue('firstDay', $firstDay);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function selectSchoolClosuresByTerm($gibbonSchoolYearTermID)
    {
        $data = array('gibbonSchoolYearTermID' => $gibbonSchoolYearTermID);
        $sql = "SELECT date, name
                FROM gibbonSchoolYearSpecialDay
                WHERE gibbonSchoolYearTermID=:gibbonSchoolYearTermID
                AND type='School Closure'
                ORDER BY date";

        return $this->db()->select($sql, $data);
    }

    public function getCurrentTermByDate($date)
    {
        $data = array('date' => $date);
        $sql = "SELECT gibbonSchoolYearTermID, gibbonSchoolYearID, name, sequenceNumber, firstDay, lastDay
                FROM gibbonSchoolYearTerm
                WHERE firstDay<=:date AND lastDay>=:date
                LIMIT 0, 1";

        $result = $this->db()->select($sql, $data);
        return ($result->rowCount() == 1) ? $result->fetch() : false;
    }

    /**
     * Select a list of school year term ID and names in the specified school year.
     *
     * @param integer $gibbonSchoolYearID  The ID of the school year.
     *
     * @return Result
     */
    public function selectTermsBySchoolYear(int $gibbonSchoolYearID): Result
    {
        $sql = 'SELECT gibbonSchoolYearTermID, name FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY sequenceNumber';
        return $this->db()->select($sql, [
            'gibbonSchoolYearID' => $gibbonSchoolYearID,
        ]);
    }

    /**
     * Get a list of school year term names based on an ID or list of IDs.
     *
     * @param string|array $gibbonSchoolYearTermID  The IDs of the school year terms.
     *
     * @return array
     */
    public function getTermNamesByID($gibbonSchoolYearTermID): array
    {
        $sql = 'SELECT name FROM gibbonSchoolYearTerm WHERE FIND_IN_SET(gibbonSchoolYearTermID, :gibbonSchoolYearTermIDList) ORDER BY sequenceNumber';
        return $this->db()->select($sql, [
            'gibbonSchoolYearTermIDList' => is_array($gibbonSchoolYearTermID)? implode(',', $gibbonSchoolYearTermID) : $gibbonSchoolYearTermID,
        ])->fetchAll(\PDO::FETCH_COLUMN, 0);
    }

}
