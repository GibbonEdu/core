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

namespace Gibbon\Domain\Timetable;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * @version v21
 * @since   v16
 */
class TimetableColumnGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonTTColumn';
    private static $primaryKey = 'gibbonTTColumnID';

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryTTColumns(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonTTColumn.gibbonTTColumnID', 'gibbonTTColumn.name', 'gibbonTTColumn.nameShort', 'COUNT(gibbonTTColumnRowID) as rowCount',
            ])
            ->leftJoin('gibbonTTColumnRow', 'gibbonTTColumnRow.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID')
            ->groupBy(['gibbonTTColumn.gibbonTTColumnID']);

        return $this->runQuery($query, $criteria);
    }

    public function selectTTColumns()
    {
        $sql = "SELECT gibbonTTColumn.gibbonTTColumnID, gibbonTTColumn.name, gibbonTTColumn.nameShort, COUNT(gibbonTTColumnRowID) as rowCount
                FROM gibbonTTColumn
                LEFT JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID)
                GROUP BY gibbonTTColumn.gibbonTTColumnID
                ORDER BY gibbonTTColumn.name";

        return $this->db()->select($sql);
    }

    public function getTTColumnByID($gibbonTTColumnID)
    {
        $data = array('gibbonTTColumnID' => $gibbonTTColumnID);
        $sql = "SELECT gibbonTTColumnID, name, nameShort FROM gibbonTTColumn WHERE gibbonTTColumnID=:gibbonTTColumnID";

        return $this->db()->selectOne($sql, $data);
    }

    public function selectTTColumnRowsByID($gibbonTTColumnID)
    {
        $data = array('gibbonTTColumnID' => $gibbonTTColumnID);
        $sql = "SELECT gibbonTTColumnRowID, name, nameShort, timeStart, timeEnd, type
                FROM gibbonTTColumnRow
                WHERE gibbonTTColumnID=:gibbonTTColumnID
                ORDER BY timeStart, name";

        return $this->db()->select($sql, $data);
    }

    public function insertColumnRow(array $data)
    {
        $sql = "INSERT INTO gibbonTTColumnRow SET gibbonTTColumnID=:gibbonTTColumnID, name=:name, nameShort=:nameShort, timeStart=:timeStart, timeEnd=:timeEnd, type=:type ON DUPLICATE KEY UPDATE gibbonTTColumnID=:gibbonTTColumnID";

        return $this->db()->insert($sql, $data);
    }
}
