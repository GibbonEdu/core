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

namespace Gibbon\Domain\Activities;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * Activity Staff Gateway
 *
 * @version v22
 * @since   v22
 */
class ActivityStaffGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonActivityStaff';
    private static $primaryKey = 'gibbonActivityStaffID';

    private static $searchableColumns = [];

    public function selectActivityStaff($gibbonActivityID) {
        $select = $this
            ->newSelect()
            ->cols(['preferredName, surname, gibbonActivityStaff.*'])
            ->from($this->getTableName())
            ->leftJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonActivityStaff.gibbonPersonID')
            ->where('gibbonActivityStaff.gibbonActivityID = :gibbonActivityID')
            ->bindValue('gibbonActivityID', $gibbonActivityID)
            ->where('gibbonPerson.status="Full"')
            ->orderBy(['surname', 'preferredName']);

        return $this->runSelect($select);
    }

    public function selectActivityOrganiserByPerson($gibbonActivityID, $gibbonPersonID) {
        $data = ['gibbonPersonID' => $gibbonPersonID, 'gibbonActivityID' => $gibbonActivityID];
        $sql = "SELECT gibbonActivity.*, NULL as status, gibbonActivityStaff.role FROM gibbonActivity JOIN gibbonActivityStaff ON (gibbonActivity.gibbonActivityID=gibbonActivityStaff.gibbonActivityID) WHERE gibbonActivity.gibbonActivityID=:gibbonActivityID AND gibbonActivityStaff.gibbonPersonID=:gibbonPersonID AND gibbonActivityStaff.role='Organiser' AND active='Y' ORDER BY name";

        return $this->db()->select($sql, $data);
    }

    public function selectActivityStaffByID($gibbonActivityID, $gibbonPersonID) {
        return $this->selectBy([
            'gibbonPersonID' 	=> $gibbonPersonID,
            'gibbonActivityID' 	=> $gibbonActivityID
        ]);
    }

    public function insertActivityStaff($gibbonActivityID, $gibbonPersonID, $role) {
        return $this->insert([
            'gibbonPersonID' 	=> $gibbonPersonID,
            'gibbonActivityID' 	=> $gibbonActivityID,
            'role'				=> $role
        ]);
    }
}
