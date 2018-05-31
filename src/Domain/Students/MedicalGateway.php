<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

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

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * @version v16
 * @since   v16
 */
class MedicalGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonPersonMedical';

    private static $searchableColumns = ['preferredName', 'surname', 'username'];
    
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
                'gibbonPersonMedicalID', 'bloodType', 'longTermMedication', 'longTermMedicationDetails', 'tetanusWithin10Years', 'comment', 'gibbonPerson.gibbonPersonID', 'gibbonPerson.preferredName', 'gibbonPerson.surname', 'gibbonRollGroup.name as rollGroup', '(SELECT COUNT(*) FROM gibbonPersonMedicalCondition WHERE gibbonPersonMedicalCondition.gibbonPersonMedicalID=gibbonPersonMedical.gibbonPersonMedicalID) as conditionCount'
            ])
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonPersonMedical.gibbonPersonID')
            ->innerJoin('gibbonStudentEnrolment', 'gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonYearGroup', 'gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID')
            ->innerJoin('gibbonRollGroup', 'gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID')
            ->where("gibbonPerson.status = 'Full'")
            ->where('gibbonStudentEnrolment.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        return $this->runQuery($query, $criteria);
    }

    public function selectMedicalConditionsByID($gibbonPersonMedicalID)
    {
        $data = array('gibbonPersonMedicalID' => $gibbonPersonMedicalID);
        $sql = "SELECT gibbonPersonMedicalCondition.*, gibbonAlertLevel.name AS risk, (CASE WHEN gibbonMedicalCondition.gibbonMedicalConditionID IS NOT NULL THEN gibbonMedicalCondition.name ELSE gibbonPersonMedicalCondition.name END) as name 
                FROM gibbonPersonMedicalCondition 
                JOIN gibbonAlertLevel ON (gibbonPersonMedicalCondition.gibbonAlertLevelID=gibbonAlertLevel.gibbonAlertLevelID) 
                LEFT JOIN gibbonMedicalCondition ON (gibbonMedicalCondition.gibbonMedicalConditionID=gibbonPersonMedicalCondition.name)
                WHERE gibbonPersonMedicalCondition.gibbonPersonMedicalID=:gibbonPersonMedicalID 
                ORDER BY gibbonPersonMedicalCondition.name";

        return $this->db()->select($sql, $data);
    }

    public function getMedicalFormByID($gibbonPersonMedicalID)
    {
        $data = array('gibbonPersonMedicalID' => $gibbonPersonMedicalID);
        $sql = "SELECT gibbonPersonMedical.*, surname, preferredName
                FROM gibbonPersonMedical
                JOIN gibbonPerson ON (gibbonPersonMedical.gibbonPersonID=gibbonPerson.gibbonPersonID)
                WHERE gibbonPersonMedicalID=:gibbonPersonMedicalID";

        return $this->db()->selectOne($sql, $data);
    }

    public function getMedicalConditionByID($gibbonPersonMedicalConditionID)
    {
        $data = array('gibbonPersonMedicalConditionID' => $gibbonPersonMedicalConditionID);
        $sql = "SELECT gibbonPersonMedicalCondition.*, (CASE WHEN gibbonMedicalCondition.gibbonMedicalConditionID IS NOT NULL THEN gibbonMedicalCondition.name ELSE gibbonPersonMedicalCondition.name END) as name, surname, preferredName
                FROM gibbonPersonMedicalCondition
                JOIN gibbonPersonMedical ON (gibbonPersonMedicalCondition.gibbonPersonMedicalID=gibbonPersonMedical.gibbonPersonMedicalID)
                JOIN gibbonPerson ON (gibbonPersonMedical.gibbonPersonID=gibbonPerson.gibbonPersonID)
                LEFT JOIN gibbonMedicalCondition ON (gibbonMedicalCondition.gibbonMedicalConditionID=gibbonPersonMedicalCondition.name)
                WHERE gibbonPersonMedicalConditionID=:gibbonPersonMedicalConditionID";

        return $this->db()->selectOne($sql, $data);
    }
}
