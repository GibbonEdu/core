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

namespace Gibbon\Module\FreeLearning\Domain;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

class UnitAuthorGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'freeLearningUnitAuthor';
    private static $primaryKey = 'freeLearningUnitAuthorID';
    private static $searchableColumns = ['freeLearningUnitAuthor.surname', 'freeLearningUnitAuthor.preferredName'];

    public function selectAuthorsByUnit($freeLearningUnitID)
    {
        $data = ['freeLearningUnitID' => $freeLearningUnitID];
        $sql = "SELECT * FROM freeLearningUnitAuthor WHERE freeLearningUnitID=:freeLearningUnitID ORDER BY surname";

        return $this->db()->select($sql, $data);
    }

    public function selectAuthorDetailsByUnitID($freeLearningUnitID) {
        $data = ['freeLearningUnitID' => $freeLearningUnitID];
        $sql = "SELECT freeLearningUnitAuthor.freeLearningUnitAuthorID,gibbonPerson.gibbonPersonID, gibbonPerson.surname, gibbonPerson.preferredName
                FROM freeLearningUnitAuthor
                JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=freeLearningUnitAuthor.gibbonPersonID) 
                WHERE freeLearningUnitAuthor.freeLearningUnitID=:freeLearningUnitID
                ORDER BY gibbonPerson.surname";

        return $this->db()->select($sql, $data);
    }

    public function deleteAuthorsNotInList($freeLearningUnitID, $authorIDList)
    {
        $authorIDList = is_array($authorIDList) ? implode(',', $authorIDList) : $authorIDList;

        $data = ['freeLearningUnitID' => $freeLearningUnitID, 'authorIDList' => $authorIDList];
        $sql = "DELETE FROM freeLearningUnitAuthor WHERE freeLearningUnitID=:freeLearningUnitID AND NOT FIND_IN_SET(freeLearningUnitAuthorID, :authorIDList)";

        return $this->db()->delete($sql, $data);
    }
}
