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

    private static $searchableColumns = ['surname', 'preferredName'];

    public function queryActivityEnrolment($criteria, $gibbonActivityID) {
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

    public function queryAllActivityParticipants($criteria, $gibbonActivityID) {
        $query = $this
            ->newQuery()
            ->cols(['gibbonActivityStudent.gibbonActivityStudentID id', 'gibbonActivityStudent.gibbonPersonID', '"Student" as role', '"Student" as roleCategory', 'gibbonActivityStudent.status', 'surname', 'preferredName', 'gibbonFormGroup.nameShort as formGroup', 'FIND_IN_SET(gibbonActivityStudent.status, "Accepted,Pending,Waiting List,Not Accepted,Left") as sortOrder', 'gibbonActivityChoice.choice'])
            ->from('gibbonActivityStudent')
            ->innerJoin('gibbonActivity', 'gibbonActivity.gibbonActivityID=gibbonActivityStudent.gibbonActivityID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonActivityStudent.gibbonPersonID')
            ->leftJoin('gibbonStudentEnrolment', 'gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=gibbonActivity.gibbonSchoolYearID')
            ->leftJoin('gibbonFormGroup', 'gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID')
            ->leftJoin('gibbonActivityChoice', 'gibbonActivityChoice.gibbonActivityChoiceID=gibbonActivityStudent.gibbonActivityChoiceID')
            ->where('gibbonActivityStudent.gibbonActivityID = :gibbonActivityID')
            ->bindValue('gibbonActivityID', $gibbonActivityID)
            ->where('gibbonPerson.status="Full"');

        $query->unionAll()
            ->cols(['gibbonActivityStaff.gibbonActivityStaffID as id', 'gibbonActivityStaff.gibbonPersonID', 'gibbonActivityStaff.role', '"Staff" as roleCategory', '"Staff" as status', 'surname', 'preferredName', 'NULL as formGroup', 'FIND_IN_SET(gibbonActivityStaff.role, "Organiser,Coach,Assistant,Other") as sortOrder', 'NULL as choice'])
            ->from('gibbonActivityStaff')
            ->innerJoin('gibbonActivity', 'gibbonActivity.gibbonActivityID=gibbonActivityStaff.gibbonActivityID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonActivityStaff.gibbonPersonID')
            ->innerJoin('gibbonStaff', 'gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID')
            ->where('gibbonActivityStaff.gibbonActivityID = :gibbonActivityID')
            ->bindValue('gibbonActivityID', $gibbonActivityID)
            ->where('gibbonPerson.status="Full"');

        return $this->runQuery($query, $criteria);
    }

    public function queryUnenrolledStudentsByCategory($criteria, $gibbonActivityCategoryID)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonActivityCategory')
            ->cols([
                'gibbonStudentEnrolment.gibbonPersonID as groupBy',
                '0 as gibbonActivityID',
                'gibbonActivityCategory.gibbonActivityCategoryID',
                'gibbonActivityCategory.name as categoryName',
                'gibbonActivityCategory.nameShort as categoryNameShort',
                'gibbonStudentEnrolment.gibbonPersonID',
                'gibbonPerson.surname',
                'gibbonPerson.preferredName',
                'gibbonPerson.email',
                'gibbonPerson.image_240',
                'gibbonFormGroup.name as formGroup',
                'gibbonYearGroup.name as yearGroup',
                'gibbonYearGroup.sequenceNumber as yearGroupSequence',
                'MIN(CASE WHEN gibbonActivityChoice.choice=1 THEN gibbonActivityChoice.gibbonActivityID END) as choice1',
                'MIN(CASE WHEN gibbonActivityChoice.choice=2 THEN gibbonActivityChoice.gibbonActivityID END) as choice2',
                'MIN(CASE WHEN gibbonActivityChoice.choice=3 THEN gibbonActivityChoice.gibbonActivityID END) as choice3',
                'MIN(CASE WHEN gibbonActivityChoice.choice=4 THEN gibbonActivityChoice.gibbonActivityID END) as choice4',
                'MIN(CASE WHEN gibbonActivityChoice.choice=5 THEN gibbonActivityChoice.gibbonActivityID END) as choice5',
                "GROUP_CONCAT(DISTINCT choiceActivity.name ORDER BY gibbonActivityChoice.choice SEPARATOR ',') as choices",
            ])
            ->innerJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonSchoolYearID=gibbonActivityCategory.gibbonSchoolYearID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonYearGroup', 'gibbonYearGroup.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID')
            ->innerJoin('gibbonFormGroup', 'gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID')
            
            ->leftJoin('gibbonActivity', 'gibbonActivity.gibbonActivityCategoryID=gibbonActivityCategory.gibbonActivityCategoryID AND gibbonActivity.gibbonSchoolYearID=gibbonActivityCategory.gibbonSchoolYearID')
            ->leftJoin('gibbonActivityStudent', 'gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonActivity.gibbonActivityID=gibbonActivityStudent.gibbonActivityID')

            ->leftJoin('gibbonActivityChoice', 'gibbonActivityChoice.gibbonActivityCategoryID=gibbonActivityCategory.gibbonActivityCategoryID AND gibbonActivityChoice.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->leftJoin('gibbonActivity AS choiceActivity', 'choiceActivity.gibbonActivityID=gibbonActivityChoice.gibbonActivityID')

            ->where('gibbonActivityCategory.gibbonActivityCategoryID=:gibbonActivityCategoryID')
            ->bindValue('gibbonActivityCategoryID', $gibbonActivityCategoryID)
            // ->where('FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonActivity.gibbonYearGroupIDList)')
            ->where("gibbonPerson.status = 'Full'")
            ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart <= :today)')
            ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd >= :today)')
            ->bindValue('today', date('Y-m-d'))
            ->having('COUNT(gibbonActivityStudent.gibbonActivityStudentID) = 0')
            ->groupBy(['gibbonPerson.gibbonPersonID']);

        return $this->runQuery($query, $criteria);
    }

    public function selectEnrolmentsByCategory($gibbonActivityCategoryID)
    {
        $data = ['gibbonActivityCategoryID' => $gibbonActivityCategoryID, 'today' => date('Y-m-d')];
        $sql = "SELECT gibbonActivityStudent.gibbonPersonID as groupBy,
                    gibbonActivityStudent.gibbonActivityID,
                    gibbonActivityStudent.gibbonPersonID,
                    gibbonActivityStudent.status,
                    gibbonActivityChoice.timestampCreated,
                    gibbonPerson.surname,
                    gibbonPerson.preferredName,
                    gibbonFormGroup.name as formGroup,
                    gibbonYearGroup.name as yearGroup,
                    gibbonYearGroup.sequenceNumber as yearGroupSequence,
                    MIN(CASE WHEN gibbonActivityChoice.choice=1 THEN gibbonActivityChoice.gibbonActivityID END) as choice1,
                    MIN(CASE WHEN gibbonActivityChoice.choice=2 THEN gibbonActivityChoice.gibbonActivityID END) as choice2,
                    MIN(CASE WHEN gibbonActivityChoice.choice=3 THEN gibbonActivityChoice.gibbonActivityID END) as choice3,
                    MIN(CASE WHEN gibbonActivityChoice.choice=4 THEN gibbonActivityChoice.gibbonActivityID END) as choice4,
                    MIN(CASE WHEN gibbonActivityChoice.choice=5 THEN gibbonActivityChoice.gibbonActivityID END) as choice5
                FROM gibbonActivityStudent
                JOIN gibbonActivity ON (gibbonActivity.gibbonActivityID=gibbonActivityStudent.gibbonActivityID)
                JOIN gibbonActivityCategory ON (gibbonActivityCategory.gibbonActivityCategoryID=gibbonActivity.gibbonActivityCategoryID)
                JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonActivityStudent.gibbonPersonID)
                LEFT JOIN gibbonActivityChoice ON (gibbonActivity.gibbonActivityCategoryID=gibbonActivityChoice.gibbonActivityCategoryID AND gibbonActivityChoice.gibbonPersonID=gibbonActivityStudent.gibbonPersonID)
                LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=gibbonActivityCategory.gibbonSchoolYearID)
                LEFT JOIN gibbonFormGroup ON (gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID)
                LEFT JOIN gibbonYearGroup ON (gibbonYearGroup.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID)
                WHERE gibbonActivityCategory.gibbonActivityCategoryID=:gibbonActivityCategoryID 
                AND gibbonPerson.status = 'Full'
                AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart <= :today)
                AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd >= :today)
                GROUP BY gibbonActivityStudent.gibbonPersonID
                ORDER BY gibbonYearGroup.sequenceNumber, gibbonFormGroup.name, gibbonPerson.surname, gibbonPerson.preferredName";

        return $this->db()->select($sql, $data);
    }

    public function getEnrolmentByCategoryAndPerson($gibbonActivityCategoryID, $gibbonPersonID)
    {
        $data = ['gibbonActivityCategoryID' => $gibbonActivityCategoryID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT gibbonActivityStudent.*, gibbonActivity.name as activityName
                FROM gibbonActivityStudent
                JOIN gibbonActivity ON (gibbonActivity.gibbonActivityID=gibbonActivityStudent.gibbonActivityID)
                WHERE gibbonActivity.gibbonActivityCategoryID=:gibbonActivityCategoryID
                AND gibbonActivityStudent.gibbonPersonID=:gibbonPersonID
                LIMIT 1";

        return $this->db()->selectOne($sql, $data);
    }

    public function deleteEnrolmentByCategoryAndPerson($gibbonActivityCategoryID, $gibbonPersonID)
    {
        $data = ['gibbonActivityCategoryID' => $gibbonActivityCategoryID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = "DELETE gibbonActivityStudent
                FROM gibbonActivityStudent
                JOIN gibbonActivity ON (gibbonActivity.gibbonActivityID=gibbonActivityStudent.gibbonActivityID)
                WHERE gibbonActivity.gibbonActivityCategoryID=:gibbonActivityCategoryID
                AND gibbonActivityStudent.gibbonPersonID=:gibbonPersonID";

        return $this->db()->selectOne($sql, $data);
    }

}
