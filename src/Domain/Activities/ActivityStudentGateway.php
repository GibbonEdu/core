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
 * Activity Student Gateway
 *
 * @version v27
 * @since   v27
 */
class ActivityStudentGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonActivityStudent';
    private static $primaryKey = 'gibbonActivityStudentID';

    private static $searchableColumns = [];

    public function queryActivityEnrolment($criteria, $gibbonActivityID) 
    {
        $query = $this
            ->newQuery()
            ->cols(['gibbonActivityStudent.*', 'surname', 'preferredName', 'gibbonFormGroup.nameShort as formGroup', 'FIND_IN_SET(gibbonActivityStudent.status, "Accepted,Pending,Waiting List,Not Accepted,Left") as sortOrder'])
            ->from($this->getTableName())
            ->innerJoin('gibbonActivity', 'gibbonActivity.gibbonActivityID=gibbonActivityStudent.gibbonActivityID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonActivityStudent.gibbonPersonID')
            ->leftJoin('gibbonStudentEnrolment', 'gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=gibbonActivity.gibbonSchoolYearID')
            ->leftJoin('gibbonFormGroup', 'gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID')
            ->where('gibbonActivityStudent.gibbonActivityID = :gibbonActivityID')
            ->bindValue('gibbonActivityID', $gibbonActivityID)
            ->where('gibbonPerson.status="Full"');

        return $this->runQuery($query, $criteria);
    }

    public function selectActivityByStudents($gibbonActivityID) 
    {
        $data = ['gibbonActivityID' => $gibbonActivityID];
        $sql = "SELECT gibbonSchoolYearTermIDList, maxParticipants, programStart, programEnd, (SELECT COUNT(*) FROM gibbonActivityStudent JOIN gibbonPerson ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID AND gibbonActivityStudent.status='Waiting List' AND gibbonPerson.status='Full') AS waiting FROM gibbonActivity WHERE gibbonActivityID=:gibbonActivityID";

        return $this->db()->select($sql, $data);
    }

    public function selectAcceptedStudentsByActivity($gibbonActivityID)
    {
        $data= ['gibbonActivityID' => $gibbonActivityID];
        $sql = "SELECT title, preferredName, surname FROM gibbonActivityStudent JOIN gibbonPerson ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonActivityID=:gibbonActivityID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonActivityStudent.status='Accepted' ORDER BY surname, preferredName";

        return $this->db()->select($sql, $data);
    }

    public function selectsWaitingListStudentsByActivity($gibbonActivityID)
    {
        $data = ['gibbonActivityID' => $gibbonActivityID];
        $sql = "SELECT title, preferredName, surname FROM gibbonActivityStudent JOIN gibbonPerson ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonActivityID=:gibbonActivityID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonActivityStudent.status='Waiting List' ORDER BY timestamp";

        return $this->db()->select($sql, $data);
    }

    public function getExistingRegistration($gibbonActivityID, $gibbonPersonID)
    {
        $data = ['gibbonActivityID' => $gibbonActivityID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = 'SELECT * FROM gibbonActivityStudent WHERE gibbonActivityID=:gibbonActivityID AND gibbonPersonID=:gibbonPersonID';

        return $this->db()->select($sql, $data);
    }

    public function selectCurrentActivityRegistrationsOfStudent($gibbonSchoolYearID, $gibbonPersonID, $term)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearTermIDList' => '%'.$term.'%'];
        $sql = "SELECT * FROM gibbonActivityStudent JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearTermIDList LIKE :gibbonSchoolYearTermIDList AND NOT status='Not Accepted'";

        return $this->db()->select($sql, $data);
    }

    public function getRegistrationConfirmation($gibbonActivityID, $gibbonPersonID)
    {
        $data = ['gibbonActivityID' => $gibbonActivityID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = 'SELECT gibbonActivityStudentID, status FROM gibbonActivityStudent WHERE gibbonActivityID=:gibbonActivityID AND gibbonPersonID=:gibbonPersonID';

        return $this->db()->select($sql, $data);
    }

    public function selectNumberOfPeopleRegisteredForActivity($gibbonActivityID)
    {
        $data= ['gibbonActivityID' => $gibbonActivityID, 'today' => date('Y-m-d')];
        $sql= "SELECT * FROM gibbonActivityStudent JOIN gibbonPerson ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL  OR dateEnd>=:today) AND gibbonActivityID=:gibbonActivityID";

        return $this->db()->select($sql, $data);
    }

    public function selectStudentsInWaitingListByActivityForBump($gibbonActivityID, $spaces)
    {
        $data = ['gibbonActivityID' => $gibbonActivityID, 'today' => date('Y-m-d')];
        $sql = "SELECT gibbonActivityStudentID, name, gibbonPerson.gibbonPersonID, surname, preferredName
            FROM gibbonActivityStudent JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) JOIN gibbonPerson ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL  OR dateEnd>=:today) AND gibbonActivityStudent.gibbonActivityID=:gibbonActivityID AND gibbonActivityStudent.status='Waiting List'
        ORDER BY timestamp ASC LIMIT 0, $spaces";
        
        return $this->db()->select($sql, $data);
    }
    public function selectActivityEnrolmentByStudent($gibbonSchoolYearID, $gibbonPersonID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID);
        $sql = "SELECT gibbonActivity.gibbonActivityID AS groupBy, gibbonActivityStudent.* FROM gibbonActivityStudent JOIN gibbonActivity ON (gibbonActivity.gibbonActivityID=gibbonActivityStudent.gibbonActivityID) WHERE gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonActivityStudent.gibbonPersonID=:gibbonPersonID";

        return $this->db()->select($sql, $data);
    }
}
