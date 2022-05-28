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

class FormUploadGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonFormUpload';
    private static $primaryKey = 'gibbonFormUploadID';
    private static $searchableColumns = ['gibbonFormUpload.name'];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryUploadsByForm(QueryCriteria $criteria, $gibbonFormID)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols(['gibbonFormUpload.gibbonFormUploadID', 'gibbonFormUpload.name'])
            ->where('gibbonFormUpload.gibbonFormID=:gibbonFormID')
            ->bindValue('gibbonFormID', $gibbonFormID);

        return $this->runQuery($query, $criteria);
    }

    public function getUploadByContext($gibbonFormID, $foreignTable, $foreignTableID, $name)
    {
        $select = $this
            ->newSelect()
            ->cols(['gibbonFormUpload.gibbonFormUploadID', 'gibbonFormUpload.path'])
            ->from($this->getTableName())
            ->where('gibbonFormUpload.gibbonFormID=:gibbonFormID', ['gibbonFormID' => $gibbonFormID])
            ->where('gibbonFormUpload.foreignTable=:foreignTable', ['foreignTable' => $foreignTable])
            ->where('gibbonFormUpload.foreignTableID=:foreignTableID', ['foreignTableID' => $foreignTableID])
            ->where('gibbonFormUpload.name=:name', ['name' => $name]);

        return $this->runSelect($select)->fetch();
    }

    public function selectAllUploadsByContext($gibbonFormID, $foreignTable, $foreignTableID)
    {
        $select = $this
            ->newSelect()
            ->cols(['gibbonFormUpload.name', 'gibbonFormUpload.path'])
            ->from($this->getTableName())
            ->where('gibbonFormUpload.gibbonFormID=:gibbonFormID', ['gibbonFormID' => $gibbonFormID])
            ->where('gibbonFormUpload.foreignTable=:foreignTable', ['foreignTable' => $foreignTable])
            ->where('gibbonFormUpload.foreignTableID=:foreignTableID', ['foreignTableID' => $foreignTableID]);

        return $this->runSelect($select);
    }
}
