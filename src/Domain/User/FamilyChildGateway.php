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
class FamilyChildGateway extends QueryableGateway implements ScrubbableGateway
{
    use TableAware;
    use Scrubbable;
    use ScrubByPerson;

    private static $tableName = 'gibbonFamilyChild';
    private static $primaryKey = 'gibbonFamilyChildID';

    private static $searchableColumns = [''];

    private static $scrubbableKey = 'gibbonPersonID';
    private static $scrubbableColumns = ['comment' => ''];

    public function selectChildrenByFamilyID($gibbonFamilyID, $gibbonSchoolYearID) {
        $data = ['gibbonFamilyID' => $gibbonFamilyID, 'gibbonSchoolYearID' => $gibbonSchoolYearID, 'date' => date('Y-m-d')];
        $sql = "SELECT * FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<=:date) AND (dateEnd IS NULL  OR dateEnd>=:date) AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName ";

        return $this->db()->select($sql, $data);
    }

    public function selectChildByFamilyAdultID($gibbonPersonID, $gibbonPersonID2)
    {
        $data = ['gibbonPersonID' => $gibbonPersonID, 'gibbonPersonID2' => $gibbonPersonID2];
        $sql = "SELECT * FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2 AND childDataAccess='Y'";

        return $this->db()->select($sql, $data);
    }
}
