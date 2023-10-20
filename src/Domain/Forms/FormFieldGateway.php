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

namespace Gibbon\Domain\Forms;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

class FormFieldGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonFormField';
    private static $primaryKey = 'gibbonFormFieldID';
    private static $searchableColumns = ['gibbonFormField.fieldName'];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryFieldsByPage(QueryCriteria $criteria, $gibbonFormPageID)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols(['gibbonFormField.gibbonFormFieldID', 'gibbonFormField.*'])
            ->where('gibbonFormField.gibbonFormPageID=:gibbonFormPageID')
            ->bindValue('gibbonFormPageID', $gibbonFormPageID);

        return $this->runQuery($query, $criteria);
    }

    public function selectFieldOrderByPage($gibbonFormPageID)
    {
        $select = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols(['sequenceNumber', 'fieldType', 'label'])
            ->where('gibbonFormPageID=:gibbonFormPageID')
            ->bindValue('gibbonFormPageID', $gibbonFormPageID)
            ->orderBy(['sequenceNumber']);

        return $this->runSelect($select);
    }

    public function getNextSequenceNumberByPage($gibbonFormPageID)
    {
        $select = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols(['MAX(sequenceNumber) + 1 as sequenceNumber'])
            ->where('gibbonFormPageID=:gibbonFormPageID')
            ->bindValue('gibbonFormPageID', $gibbonFormPageID);

        return $this->runSelect($select)->fetchColumn(0);
    }

    public function bumpSequenceNumbersByAmount($gibbonFormPageID, $sequenceNumber, $amount) {
        $data = ['gibbonFormPageID' => $gibbonFormPageID, 'sequenceNumber' => $sequenceNumber, 'amount' => $amount];
        $sql = "UPDATE gibbonFormField 
                SET sequenceNumber=sequenceNumber+:amount 
                WHERE sequenceNumber>:sequenceNumber 
                AND gibbonFormPageID=:gibbonFormPageID";

        return $this->db()->update($sql, $data);
    }

    public function getFieldInForm($gibbonFormID, $fieldName)
    {
        $data = ['gibbonFormID' => $gibbonFormID, 'fieldName' => $fieldName];
        $sql = "SELECT gibbonFormFieldID 
                FROM gibbonFormField 
                JOIN gibbonFormPage ON (gibbonFormPage.gibbonFormPageID=gibbonFormField.gibbonFormPageID)
                WHERE gibbonFormPage.gibbonFormID=:gibbonFormID 
                AND gibbonFormField.fieldName=:fieldName";

        return $this->db()->selectOne($sql, $data);
    }

}
