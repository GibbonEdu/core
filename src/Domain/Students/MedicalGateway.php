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

namespace Gibbon\Domain\Students;

use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\ScrubbableGateway;
use Gibbon\Domain\Traits\Scrubbable;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\Traits\ScrubByPerson;

/**
 * @version v16
 * @since   v16
 */
class MedicalGateway extends QueryableGateway implements ScrubbableGateway
{
    use TableAware;
    use Scrubbable;
    use ScrubByPerson;

    private static $tableName = 'gibbonPersonMedical';
    private static $primaryKey = 'gibbonPersonMedicalID';

    private static $searchableColumns = ['preferredName', 'surname', 'username'];

    private static $scrubbableKey = 'gibbonPersonID';
    private static $scrubbableColumns = ['longTermMedication' => '','longTermMedicationDetails' => '','comment' => '', 'fields' => ''];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryMedicalFormsBySchoolYear(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonPersonMedicalID', 'longTermMedication', 'longTermMedicationDetails', 'comment', 'gibbonPerson.gibbonPersonID', 'gibbonPerson.preferredName', 'gibbonPerson.surname', 'gibbonFormGroup.name as formGroup', '(SELECT COUNT(*) FROM gibbonPersonMedicalCondition WHERE gibbonPersonMedicalCondition.gibbonPersonMedicalID=gibbonPersonMedical.gibbonPersonMedicalID) as conditionCount'
            ])
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonPersonMedical.gibbonPersonID')
            ->innerJoin('gibbonStudentEnrolment', 'gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonYearGroup', 'gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID')
            ->innerJoin('gibbonFormGroup', 'gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID')
            ->where("gibbonPerson.status = 'Full'")
            ->where('gibbonStudentEnrolment.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        return $this->runQuery($query, $criteria);
    }

    public function selectMedicalConditionsByID($gibbonPersonMedicalID)
    {
        $gibbonPersonMedicalID = is_array($gibbonPersonMedicalID) ? implode(',', $gibbonPersonMedicalID) : $gibbonPersonMedicalID;

        $data = array('gibbonPersonMedicalID' => $gibbonPersonMedicalID);
        $sql = "SELECT gibbonPersonMedicalCondition.gibbonPersonMedicalID, gibbonPersonMedicalCondition.*, gibbonAlertLevel.name AS risk, gibbonAlertLevel.color as alertColor, (CASE WHEN gibbonMedicalCondition.gibbonMedicalConditionID IS NOT NULL THEN gibbonMedicalCondition.name ELSE gibbonPersonMedicalCondition.name END) as name , gibbonMedicalCondition.description
                FROM gibbonPersonMedicalCondition
                JOIN gibbonAlertLevel ON (gibbonPersonMedicalCondition.gibbonAlertLevelID=gibbonAlertLevel.gibbonAlertLevelID)
                LEFT JOIN gibbonMedicalCondition ON (gibbonMedicalCondition.gibbonMedicalConditionID=gibbonPersonMedicalCondition.name OR gibbonMedicalCondition.name=gibbonPersonMedicalCondition.name)
                WHERE FIND_IN_SET(gibbonPersonMedicalCondition.gibbonPersonMedicalID, :gibbonPersonMedicalID)
                ORDER BY gibbonAlertLevel.sequenceNumber DESC, gibbonPersonMedicalCondition.name";

        return $this->db()->select($sql, $data);
    }

    public function getMedicalFormByPerson($gibbonPersonID)
    {
        $data = array('gibbonPersonID' => $gibbonPersonID);
        $sql = "SELECT gibbonPersonMedical.*, surname, preferredName
                FROM gibbonPersonMedical
                JOIN gibbonPerson ON (gibbonPersonMedical.gibbonPersonID=gibbonPerson.gibbonPersonID)
                WHERE gibbonPersonMedical.gibbonPersonID=:gibbonPersonID";

        return $this->db()->selectOne($sql, $data);
    }

    public function getMedicalFormByID($gibbonPersonMedicalID)
    {
        $data = array('gibbonPersonMedicalID' => $gibbonPersonMedicalID);
        $sql = "SELECT gibbonPersonMedical.*, surname, preferredName
                FROM gibbonPersonMedical
                JOIN gibbonPerson ON (gibbonPersonMedical.gibbonPersonID=gibbonPerson.gibbonPersonID)
                WHERE gibbonPersonMedical.gibbonPersonMedicalID=:gibbonPersonMedicalID";

        return $this->db()->selectOne($sql, $data);
    }

    public function getMedicalConditionByID($gibbonPersonMedicalConditionID)
    {
        $data = array('gibbonPersonMedicalConditionID' => $gibbonPersonMedicalConditionID);
        $sql = "SELECT gibbonPersonMedicalCondition.*, (CASE WHEN gibbonMedicalCondition.gibbonMedicalConditionID IS NOT NULL THEN gibbonMedicalCondition.name ELSE gibbonPersonMedicalCondition.name END) as name, surname, preferredName, gibbonPerson.gibbonPersonID
                FROM gibbonPersonMedicalCondition
                JOIN gibbonPersonMedical ON (gibbonPersonMedicalCondition.gibbonPersonMedicalID=gibbonPersonMedical.gibbonPersonMedicalID)
                JOIN gibbonPerson ON (gibbonPersonMedical.gibbonPersonID=gibbonPerson.gibbonPersonID)
                LEFT JOIN gibbonMedicalCondition ON (gibbonMedicalCondition.gibbonMedicalConditionID=gibbonPersonMedicalCondition.name)
                WHERE gibbonPersonMedicalConditionID=:gibbonPersonMedicalConditionID";

        return $this->db()->selectOne($sql, $data);
    }

    /**
     * Get the risk level of the highest-risk condition for an individual.
     *
     * @version  v25
     * @since    v25
     *
     * @param int   $gibbonPersonID  The person ID.
     *
     * @return array  An array of fields in the medical alert information of the person,
     *                or an empty array if none found.
     */
    public function getHighestMedicalRisk(int $gibbonPersonID)
    {
        $sql = 'SELECT * FROM gibbonPersonMedical JOIN gibbonPersonMedicalCondition ON (gibbonPersonMedical.gibbonPersonMedicalID=gibbonPersonMedicalCondition.gibbonPersonMedicalID) JOIN gibbonAlertLevel ON (gibbonPersonMedicalCondition.gibbonAlertLevelID=gibbonAlertLevel.gibbonAlertLevelID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY gibbonAlertLevel.sequenceNumber DESC';
        return $this->db()->selectOne($sql, ['gibbonPersonID' => $gibbonPersonID]);
    }
}
