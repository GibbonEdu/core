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

namespace Gibbon\Domain\User;

use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\ScrubbableGateway;
use Gibbon\Domain\Traits\Scrubbable;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\Traits\ScrubByPerson;

/**
 * @version v21
 * @since   v21
 */
class FamilyAdultGateway extends QueryableGateway implements ScrubbableGateway
{
    use TableAware;
    use Scrubbable;
    use ScrubByPerson;

    private static $tableName = 'gibbonFamilyAdult';
    private static $primaryKey = 'gibbonFamilyAdultID';

    private static $searchableColumns = [''];

    private static $scrubbableKey = 'gibbonPersonID';
    private static $scrubbableColumns = ['comment' => ''];

    public function deleteFamilyAdult($gibbonFamilyID, $gibbonPersonIDAdult)
    {
        $query = $this
            ->newDelete()
            ->from('gibbonFamilyAdult')
            ->where('gibbonFamilyID=:gibbonFamilyID', ['gibbonFamilyID' => $gibbonFamilyID])
            ->where('gibbonPersonID=:gibbonPersonIDAdult', ['gibbonPersonIDAdult' => $gibbonPersonIDAdult]);

        return $this->runDelete($query);
    }
    
    public function insertFamilyRelationship($gibbonFamilyID, $gibbonPersonIDAdult, $gibbonPersonIDStudent, $relationship)
    {
        $data = ['gibbonFamilyID' => $gibbonFamilyID, 'gibbonPersonID1' => $gibbonPersonIDAdult, 'gibbonPersonID2' => $gibbonPersonIDStudent];
        $sql = "SELECT gibbonFamilyRelationshipID FROM gibbonFamilyRelationship WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPersonID1=:gibbonPersonID1 AND gibbonPersonID2=:gibbonPersonID2";
        
        $existing = $this->db()->selectOne($sql, $data);

        if ($existing) {
            $query = $this
                ->newUpdate()
                ->table('gibbonFamilyRelationship')
                ->cols(['relationship' => $relationship])
                ->where('gibbonFamilyID=:gibbonFamilyID', ['gibbonFamilyID' => $gibbonFamilyID])
                ->where('gibbonPersonID1=:gibbonPersonIDAdult', ['gibbonPersonIDAdult' => $gibbonPersonIDAdult])
                ->where('gibbonPersonID2=:gibbonPersonIDStudent', ['gibbonPersonIDStudent' => $gibbonPersonIDStudent]);

            return $this->runUpdate($query);
        } else {
            $query = $this
                ->newInsert()
                ->into('gibbonFamilyRelationship')
                ->cols([
                    'gibbonFamilyID'  => $gibbonFamilyID,
                    'gibbonPersonID1' => $gibbonPersonIDAdult,
                    'gibbonPersonID2' => $gibbonPersonIDStudent,
                    'relationship'    => $relationship,
                ]);

            return $this->runInsert($query);
        }
    }

    public function deleteFamilyRelationship($gibbonFamilyID, $gibbonPersonIDAdult, $gibbonPersonIDStudent)
    {
        $query = $this
            ->newDelete()
            ->from('gibbonFamilyRelationship')
            ->where('gibbonFamilyID=:gibbonFamilyID', ['gibbonFamilyID' => $gibbonFamilyID])
            ->where('gibbonPersonID1=:gibbonPersonIDAdult', ['gibbonPersonIDAdult' => $gibbonPersonIDAdult])
            ->where('gibbonPersonID2=:gibbonPersonIDStudent', ['gibbonPersonIDStudent' => $gibbonPersonIDStudent]);

        return $this->runDelete($query);
    }
}
