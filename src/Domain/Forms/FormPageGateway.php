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

class FormPageGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonFormPage';
    private static $primaryKey = 'gibbonFormPageID';
    private static $searchableColumns = ['gibbonFormPage.name'];
    
    /**
     * @return DataSet
     */
    public function selectPagesByForm($gibbonFormID)
    {
        $query = $this
            ->newSelect()
            ->distinct()
            ->from($this->getTableName())
            ->cols(['gibbonFormPage.sequenceNumber AS groupBy', 'gibbonFormPage.gibbonFormPageID', 'gibbonFormPage.name', 'gibbonFormPage.sequenceNumber', 'gibbonFormPage.introduction', 'gibbonFormPage.postScript', '(SELECT COUNT(*) FROM gibbonFormField WHERE gibbonFormField.gibbonFormPageID=gibbonFormPage.gibbonFormPageID) as count'])
            ->where('gibbonFormPage.gibbonFormID=:gibbonFormID')
            ->bindValue('gibbonFormID', $gibbonFormID)
            ->orderBy(['gibbonFormPage.sequenceNumber']);

        return $this->runSelect($query);
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
