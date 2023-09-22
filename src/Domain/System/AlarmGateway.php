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

namespace Gibbon\Domain\System;

use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\Traits\TableAware;

/**
 * Alarm Gateway
 *
 * @version v19
 * @since   v19
 */
class AlarmGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonAlarm';
    private static $primaryKey = 'gibbonAlarmID';

    private static $searchableColumns = ['type'];
    
    public function selectAlarmConfirmation($gibbonAlarmID)
    {
        $data = ['gibbonAlarmID' => $gibbonAlarmID, 'today' => date('Y-m-d')];
        $sql = "SELECT gibbonPerson.gibbonPersonID, status, surname, preferredName, gibbonAlarmConfirmID 
                FROM gibbonPerson 
                JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) 
                LEFT JOIN gibbonAlarmConfirm ON (gibbonAlarmConfirm.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonAlarmID=:gibbonAlarmID) 
                WHERE gibbonPerson.status='Full' 
                AND (dateStart IS NULL OR dateStart<=:today) 
                AND (dateEnd IS NULL  OR dateEnd>=:today) 
                ORDER BY surname, preferredName";

        return $this->db()->select($sql, $data);
    }

    public function getAlarmConfirmationByPerson($gibbonAlarmID, $gibbonPersonID)
    {
        $data = ['gibbonAlarmID' => $gibbonAlarmID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT * FROM gibbonAlarmConfirm WHERE gibbonAlarmID=:gibbonAlarmID AND gibbonPersonID=:gibbonPersonID";

        return $this->db()->selectOne($sql, $data);
    }
    
    public function insertAlarmConfirm(array $data) {

        $query = $this
            ->newInsert()
            ->into('gibbonAlarmConfirm')
            ->cols($data);

        return $this->runInsert($query);
    }
    
}
