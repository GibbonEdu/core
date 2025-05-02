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

namespace Gibbon\Module\Interventions\Domain;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * Support Plan Note Gateway
 *
 * @version v1.0
 * @since   v1.0
 */
class INSupportPlanNoteGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonINSupportPlanNote';
    private static $primaryKey = 'gibbonINSupportPlanNoteID';
    private static $searchableColumns = ['gibbonINSupportPlanNote.title', 'gibbonINSupportPlanNote.note'];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryNotes(QueryCriteria $criteria, $gibbonINSupportPlanID = null)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonINSupportPlanNote.gibbonINSupportPlanNoteID', 
                'gibbonINSupportPlanNote.gibbonINSupportPlanID', 
                'gibbonINSupportPlanNote.gibbonPersonID', 
                'gibbonINSupportPlanNote.title', 
                'gibbonINSupportPlanNote.note',
                'gibbonINSupportPlanNote.date',
                'gibbonINSupportPlanNote.timestamp',
                'gibbonPerson.title', 
                'gibbonPerson.surname', 
                'gibbonPerson.preferredName',
                'gibbonPerson.image_240'
            ])
            ->leftJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonINSupportPlanNote.gibbonPersonID');

        if (!is_null($gibbonINSupportPlanID)) {
            $query->where('gibbonINSupportPlanNote.gibbonINSupportPlanID = :gibbonINSupportPlanID')
                  ->bindValue('gibbonINSupportPlanID', $gibbonINSupportPlanID);
        }

        $criteria->addFilterRules([
            'gibbonINSupportPlanID' => function ($query, $gibbonINSupportPlanID) {
                return $query
                    ->where('gibbonINSupportPlanNote.gibbonINSupportPlanID = :gibbonINSupportPlanID')
                    ->bindValue('gibbonINSupportPlanID', $gibbonINSupportPlanID);
            }
        ]);

        return $this->runQuery($query, $criteria);
    }
    
    /**
     * Get note by ID
     *
     * @param string $gibbonINSupportPlanNoteID
     * @return array
     */
    public function getByID($gibbonINSupportPlanNoteID)
    {
        $data = ['gibbonINSupportPlanNoteID' => $gibbonINSupportPlanNoteID];
        $sql = "SELECT gibbonINSupportPlanNote.*, 
                    gibbonPerson.title, gibbonPerson.preferredName, gibbonPerson.surname, gibbonPerson.image_240
                FROM gibbonINSupportPlanNote
                LEFT JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonINSupportPlanNote.gibbonPersonID)
                WHERE gibbonINSupportPlanNoteID=:gibbonINSupportPlanNoteID";

        return $this->db()->selectOne($sql, $data);
    }
}
