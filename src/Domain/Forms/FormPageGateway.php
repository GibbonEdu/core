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

namespace Gibbon\Domain\Forms;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

class FormPageGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonFormPage';
    private static $primaryKey = 'gibbonFormPageID';
    private static $searchableColumns = ['gibbonFormPage.name'];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryPagesByForm(QueryCriteria $criteria, $gibbonFormID)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols(['gibbonFormPage.gibbonFormPageID', 'gibbonFormPage.name', 'gibbonFormPage.sequenceNumber', '(SELECT COUNT(*) FROM gibbonFormField WHERE gibbonFormField.gibbonFormPageID=gibbonFormPage.gibbonFormPageID) as count'])
            ->where('gibbonFormPage.gibbonFormID=:gibbonFormID')
            ->bindValue('gibbonFormID', $gibbonFormID);

        return $this->runQuery($query, $criteria);
    }

    public function getPageIDByNumber($gibbonFormID, $page)
    {
        return $this->selectBy(['gibbonFormID' => $gibbonFormID, 'sequenceNumber' => $page], ['gibbonFormPageID'])->fetchColumn(0);
    }

    public function getNextPageByNumber($gibbonFormID, $page)
    {
        $data = ['gibbonFormID' => $gibbonFormID, 'page' => $page];
        $sql = "SELECT * FROM gibbonFormPage WHERE sequenceNumber=(SELECT MIN(sequenceNumber) FROM gibbonFormPage WHERE sequenceNumber > :page AND gibbonFormID=:gibbonFormID) AND gibbonFormID=:gibbonFormID";

        return $this->db()->selectOne($sql, $data);
    }

    public function getFinalPageNumber($gibbonFormID)
    {
        $data = ['gibbonFormID' => $gibbonFormID];
        $sql = "SELECT MAX(sequenceNumber) FROM gibbonFormPage WHERE gibbonFormID=:gibbonFormID";

        return $this->db()->selectOne($sql, $data);
    }

    public function getNextSequenceNumberByForm($gibbonFormID)
    {
        $select = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols(['MAX(sequenceNumber) + 1 as sequenceNumber'])
            ->where('gibbonFormID=:gibbonFormID')
            ->bindValue('gibbonFormID', $gibbonFormID);

        return $this->runSelect($select)->fetchColumn(0);
    }
}
