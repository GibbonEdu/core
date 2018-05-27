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
class StudentGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonStudentEnrolment';

    private static $searchableColumns = ['gibbonPerson.preferredName', 'gibbonPerson.surname', 'gibbonPerson.username', 'gibbonPerson.email', 'gibbonPerson.emailAlternate', 'gibbonPerson.studentID', 'gibbonPerson.phone1', 'gibbonPerson.vehicleRegistration'];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryStudentsBySchoolYear(QueryCriteria $criteria, $gibbonSchoolYearID, $searchFamilyDetails = false)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonPerson')
            ->cols([
                'gibbonPerson.gibbonPersonID', 'gibbonStudentEnrolmentID', 'gibbonPerson.preferredName', 'gibbonPerson.surname', 'gibbonYearGroup.nameShort AS yearGroup', 'gibbonRollGroup.nameShort AS rollGroup', 'gibbonPerson.dateStart', 'gibbonPerson.dateEnd', 'gibbonPerson.status'
            ])
            ->leftJoin('gibbonStudentEnrolment', 'gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->leftJoin('gibbonYearGroup', 'gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID')
            ->leftJoin('gibbonRollGroup', 'gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID');

        if ($criteria->hasFilter('all')) {
            $query->innerJoin('gibbonRole', 'FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonPerson.gibbonRoleIDAll)')
                  ->where("gibbonRole.category='Student'");
        } else {
            $query->where("gibbonPerson.status = 'Full'")
                  ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart <= :today)')
                  ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd >= :today)')
                  ->bindValue('today', date('Y-m-d'));
            
            $query->where('gibbonStudentEnrolment.gibbonSchoolYearID = :gibbonSchoolYearID')
                  ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);
        }

        if ($searchFamilyDetails && $criteria->hasSearchText()) {
            $query
                ->leftJoin('gibbonFamilyChild as child', "child.gibbonPersonID=gibbonPerson.gibbonPersonID")
                ->leftJoin('gibbonFamilyAdult as adult1', "(adult1.gibbonFamilyID=child.gibbonFamilyID AND adult1.contactPriority=1)")
                ->leftJoin('gibbonPerson as parent1', "(parent1.gibbonPersonID=adult1.gibbonPersonID AND parent1.status='Full' AND parent1.email LIKE :searchFamily)")
                ->leftJoin('gibbonFamilyAdult as adult2', "(adult2.gibbonFamilyID=child.gibbonFamilyID AND adult2.contactPriority=2)")
                ->leftJoin('gibbonPerson as parent2', "(parent2.gibbonPersonID=adult2.gibbonPersonID AND parent2.status='Full' AND parent2.email LIKE :searchFamily)")
                ->bindValue('searchFamily', $criteria->getSearchText());
        }

        return $this->runQuery($query, $criteria);
    }

    public function selectActiveStudentsByFamilyAdult($gibbonSchoolYearID, $gibbonPersonID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID, 'today' => date('Y-m-d'));
        $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup
                FROM gibbonFamilyAdult
                JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID)
                JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID)
                JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
                JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                WHERE gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID
                AND gibbonFamilyAdult.childDataAccess='Y'
                AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                AND gibbonPerson.status='Full' 
                AND (dateStart IS NULL OR dateStart<=:today) 
                AND (dateEnd IS NULL  OR dateEnd>=:today)
                GROUP BY gibbonPerson.gibbonPersonID
                ORDER BY surname, preferredName";

        return $this->db()->select($sql, $data);
    }

    public function selectActiveStudentByPerson($gibbonSchoolYearID, $gibbonPersonID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID, 'today' => date('Y-m-d'));
        $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup
                FROM gibbonPerson
                JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
                JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID 
                AND gibbonPerson.status='Full'
                AND (dateStart IS NULL OR dateStart<=:today) 
                AND (dateEnd IS NULL  OR dateEnd>=:today)
                AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID";

        return $this->db()->select($sql, $data);
    }
}
