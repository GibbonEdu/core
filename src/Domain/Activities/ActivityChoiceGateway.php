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

namespace Gibbon\Domain\Activities;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

class ActivityChoiceGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonActivityChoice';
    private static $primaryKey = 'gibbonActivityChoiceID';
    private static $searchableColumns = ['gibbonPerson.preferredName', 'gibbonPerson.surname', 'gibbonActivityCategory.name', 'gibbonActivity.name'];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryChoices(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols([
                'gibbonActivityCategory.gibbonActivityCategoryID',
                'gibbonActivityCategory.name as categoryName',
                'gibbonActivityCategory.nameShort as categoryNameShort',
                'gibbonActivityChoice.gibbonPersonID',
                'gibbonActivityChoice.timestampModified',
                'gibbonPerson.preferredName',
                'gibbonPerson.surname',
                'gibbonPerson.image_240',
                'gibbonFormGroup.nameShort as formGroup',
                'gibbonYearGroup.nameShort as yearGroup',
                "GROUP_CONCAT(gibbonActivity.name ORDER BY gibbonActivityChoice.choice SEPARATOR ',') as choices",
                "GROUP_CONCAT(CONCAT(gibbonActivityChoice.choice, ':', gibbonActivity.name) ORDER BY gibbonActivityChoice.choice SEPARATOR ',') as choiceList",
                "(CASE WHEN gibbonActivityStudent.gibbonActivityStudentID IS NOT NULL THEN enrolledActivity.name ELSE '' END) as enrolledActivity"
                
            ])
            ->innerJoin('gibbonActivity', 'gibbonActivity.gibbonActivityID=gibbonActivityChoice.gibbonActivityID')
            ->innerJoin('gibbonActivityCategory', 'gibbonActivityCategory.gibbonActivityCategoryID=gibbonActivity.gibbonActivityCategoryID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonActivityChoice.gibbonPersonID')
            ->leftJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=gibbonActivityCategory.gibbonSchoolYearID')
            ->leftJoin('gibbonFormGroup', 'gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID')
            ->leftJoin('gibbonYearGroup', 'gibbonYearGroup.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID')
            ->leftJoin('gibbonActivityStudent', 'gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID')
            ->leftJoin('gibbonActivity as enrolledActivity', 'enrolledActivity.gibbonActivityID=gibbonActivityStudent.gibbonActivityID')
            ->where('gibbonActivityCategory.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->groupBy(['gibbonActivityChoice.gibbonPersonID', 'gibbonActivityCategory.gibbonActivityCategoryID']);


        $criteria->addFilterRules([
            'category' => function ($query, $gibbonActivityCategoryID) {
                return $query
                    ->where('gibbonActivityCategory.gibbonActivityCategoryID = :gibbonActivityCategoryID')
                    ->bindValue('gibbonActivityCategoryID', $gibbonActivityCategoryID);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryChoicesByPerson(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonPersonID)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols([
                'gibbonActivityCategory.gibbonActivityCategoryID',
                'gibbonActivityCategory.name as categoryName',
                'gibbonActivityCategory.nameShort as categoryNameShort',
                'gibbonActivityChoice.gibbonPersonID',
                'gibbonActivityChoice.timestampModified',
                'gibbonPerson.preferredName',
                'gibbonPerson.surname',
                'gibbonPerson.image_240',
                "GROUP_CONCAT(gibbonActivity.name ORDER BY gibbonActivityChoice.choice SEPARATOR ',') as choices",
                
            ])
            ->innerJoin('gibbonActivity', 'gibbonActivity.gibbonActivityID=gibbonActivityChoice.gibbonActivityID')
            ->innerJoin('gibbonActivityCategory', 'gibbonActivityCategory.gibbonActivityCategoryID=gibbonActivity.gibbonActivityCategoryID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonActivityChoice.gibbonPersonID')
            ->where('gibbonActivityCategory.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonActivityChoice.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->groupBy(['gibbonActivityChoice.gibbonPersonID']);

        return $this->runQuery($query, $criteria);
    }

    public function queryNotSignedUpStudentsByCategory($criteria, $gibbonActivityCategoryID)
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
            ])
            ->innerJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonSchoolYearID=gibbonActivityCategory.gibbonSchoolYearID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonYearGroup', 'gibbonYearGroup.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID')
            ->innerJoin('gibbonFormGroup', 'gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID')

            ->leftJoin('gibbonActivityChoice', 'gibbonActivityChoice.gibbonActivityCategoryID=gibbonActivityCategory.gibbonActivityCategoryID AND gibbonActivityChoice.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->leftJoin('gibbonActivity', 'gibbonActivity.gibbonActivityID=gibbonActivityChoice.gibbonActivityID')

            ->where('gibbonActivityCategory.gibbonActivityCategoryID=:gibbonActivityCategoryID')
            ->bindValue('gibbonActivityCategoryID', $gibbonActivityCategoryID)
            ->where("gibbonPerson.status = 'Full'")
            ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart <= :today)')
            ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd >= :today)')
            ->bindValue('today', date('Y-m-d'))
            ->where('gibbonActivityChoice.gibbonActivityChoiceID IS NULL')
            ->groupBy(['gibbonPerson.gibbonPersonID']);

        return $this->runQuery($query, $criteria);
    }

    public function selectChoiceCountsByCategory($gibbonActivityCategoryID)
    {
        $data = ['gibbonActivityCategoryID' => $gibbonActivityCategoryID, 'today' => date('Y-m-d')];
        $sql = "SELECT gibbonActivity.gibbonActivityID as groupBy,
                    gibbonActivity.gibbonActivityID,
                    COUNT(DISTINCT CASE WHEN gibbonActivityChoice.choice=1 AND gibbonPerson.gibbonPersonID IS NOT NULL THEN gibbonActivityChoice.gibbonActivityChoiceID END) as choice1,
                    COUNT(DISTINCT CASE WHEN gibbonActivityChoice.choice=2 AND gibbonPerson.gibbonPersonID IS NOT NULL THEN gibbonActivityChoice.gibbonActivityChoiceID END) as choice2,
                    COUNT(DISTINCT CASE WHEN gibbonActivityChoice.choice=3 AND gibbonPerson.gibbonPersonID IS NOT NULL THEN gibbonActivityChoice.gibbonActivityChoiceID END) as choice3,
                    COUNT(DISTINCT CASE WHEN gibbonActivityChoice.choice=4 AND gibbonPerson.gibbonPersonID IS NOT NULL THEN gibbonActivityChoice.gibbonActivityChoiceID END) as choice4,
                    COUNT(DISTINCT CASE WHEN gibbonActivityChoice.choice=5 AND gibbonPerson.gibbonPersonID IS NOT NULL THEN gibbonActivityChoice.gibbonActivityChoiceID END) as choice5
                FROM gibbonActivity
                LEFT JOIN gibbonActivityChoice ON (gibbonActivityChoice.gibbonActivityID=gibbonActivity.gibbonActivityID)
                LEFT JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonActivityChoice.gibbonPersonID AND gibbonPerson.status = 'Full' AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart <= :today)
                AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd >= :today))
                WHERE gibbonActivity.gibbonActivityCategoryID=:gibbonActivityCategoryID
                GROUP BY gibbonActivity.gibbonActivityID";

        return $this->db()->select($sql, $data);
    }

    public function selectChoicesByCategory($gibbonActivityCategoryID)
    {
        $data = ['gibbonActivityCategoryID' => $gibbonActivityCategoryID, 'today' => date('Y-m-d')];
        $sql = "SELECT gibbonActivityChoice.gibbonPersonID as groupBy,
                    gibbonActivityChoice.gibbonPersonID,
                    gibbonActivityChoice.timestampCreated,
                    gibbonPerson.surname,
                    gibbonPerson.preferredName,
                    gibbonFormGroup.name as formGroup,
                    gibbonYearGroup.sequenceNumber as yearGroupSequence,
                    MIN(CASE WHEN gibbonActivityChoice.choice=1 THEN gibbonActivityChoice.gibbonActivityID END) as choice1,
                    MIN(CASE WHEN gibbonActivityChoice.choice=2 THEN gibbonActivityChoice.gibbonActivityID END) as choice2,
                    MIN(CASE WHEN gibbonActivityChoice.choice=3 THEN gibbonActivityChoice.gibbonActivityID END) as choice3,
                    MIN(CASE WHEN gibbonActivityChoice.choice=4 THEN gibbonActivityChoice.gibbonActivityID END) as choice4,
                    MIN(CASE WHEN gibbonActivityChoice.choice=5 THEN gibbonActivityChoice.gibbonActivityID END) as choice5
                FROM gibbonActivityChoice
                JOIN gibbonActivityCategory ON (gibbonActivityCategory.gibbonActivityCategoryID=gibbonActivityChoice.gibbonActivityCategoryID)
                JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonActivityChoice.gibbonPersonID)
                LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=gibbonActivityCategory.gibbonSchoolYearID)
                LEFT JOIN gibbonFormGroup ON (gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID)
                LEFT JOIN gibbonYearGroup ON (gibbonYearGroup.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID)
                WHERE gibbonActivityCategory.gibbonActivityCategoryID=:gibbonActivityCategoryID
                AND gibbonPerson.status = 'Full'
                AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart <= :today)
                AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd >= :today)
                GROUP BY gibbonActivityChoice.gibbonPersonID
                ORDER BY gibbonFormGroup.name, gibbonPerson.surname, gibbonPerson.preferredName";

        return $this->db()->select($sql, $data);
    }

    public function selectChoiceWeightingByCategory($gibbonActivityCategoryID)
    {
        $data = ['gibbonActivityCategoryID' => $gibbonActivityCategoryID];
        $sql = "SELECT gibbonActivityChoice.gibbonPersonID as groupBy,
                    gibbonActivityChoice.gibbonPersonID,
                    SUM(pastChoice.choice) as choiceCount,
                    COUNT(DISTINCT pastChoice.gibbonActivityCategoryID) as categoryCount
                FROM gibbonActivityChoice
                JOIN gibbonActivityCategory ON (gibbonActivityCategory.gibbonActivityCategoryID=gibbonActivityChoice.gibbonActivityCategoryID)
                LEFT JOIN gibbonActivityStudent AS pastEnrolment ON (pastEnrolment.gibbonPersonID=gibbonActivityChoice.gibbonPersonID AND pastEnrolment.status='Confirmed')
                LEFT JOIN gibbonActivity as pastActivity ON (pastActivity.gibbonActivityID=pastEnrolment.gibbonActivityID AND pastActivity.gibbonActivityCategoryID<>gibbonActivityCategory.gibbonActivityCategoryID)
                LEFT JOIN gibbonActivityChoice as pastChoice ON (pastChoice.gibbonActivityChoiceID=pastEnrolment.gibbonActivityChoiceID AND pastChoice.gibbonPersonID=gibbonActivityChoice.gibbonPersonID )
                WHERE gibbonActivityCategory.gibbonActivityCategoryID=:gibbonActivityCategoryID
                AND gibbonActivityChoice.choice=1
                GROUP BY gibbonActivityChoice.gibbonPersonID
                ORDER BY choiceCount DESC";

        return $this->db()->select($sql, $data);
    }

    public function getTimestampMinMaxByCategory($gibbonActivityCategoryID)
    {
        $data = ['gibbonActivityCategoryID' => $gibbonActivityCategoryID];
        $sql = "SELECT UNIX_TIMESTAMP(MIN(timestampCreated)) as min, UNIX_TIMESTAMP(MAX(timestampCreated)) as max
            FROM gibbonActivityChoice 
            WHERE gibbonActivityChoice.gibbonActivityCategoryID=:gibbonActivityCategoryID
            GROUP BY gibbonActivityChoice.gibbonActivityCategoryID";

        return $this->db()->selectOne($sql, $data);
    }

    public function getYearGroupWeightingMax()
    {
        return $this->db()->selectOne("SELECT MAX(sequenceNumber) FROM gibbonYearGroup");
    }

    public function selectChoicesByPerson($gibbonActivityCategoryID, $gibbonPersonID)
    {
        $data = ['gibbonActivityCategoryID' => $gibbonActivityCategoryID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT gibbonActivityChoice.choice as groupBy, gibbonActivityChoice.*
                FROM gibbonActivityChoice
                JOIN gibbonActivity ON (gibbonActivity.gibbonActivityID=gibbonActivityChoice.gibbonActivityID)
                WHERE gibbonActivity.gibbonActivityCategoryID=:gibbonActivityCategoryID
                AND gibbonActivityChoice.gibbonPersonID=:gibbonPersonID
                ORDER BY gibbonActivityChoice.choice";

        return $this->db()->select($sql, $data);
    }

    public function getChoiceByActivityAndPerson($gibbonActivityID, $gibbonPersonID)
    {
        $data = ['gibbonActivityID' => $gibbonActivityID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT *
                FROM gibbonActivityChoice
                WHERE gibbonActivityChoice.gibbonActivityID=:gibbonActivityID
                AND gibbonActivityChoice.gibbonPersonID=:gibbonPersonID
                LIMIT 1";

        return $this->db()->selectOne($sql, $data);
    }

    public function deleteChoicesNotInList($gibbonActivityCategoryID, $gibbonPersonID, $choiceIDs)
    {
        $choiceIDs = is_array($choiceIDs) ? implode(',', $choiceIDs) : $choiceIDs;

        $data = ['gibbonActivityCategoryID' => $gibbonActivityCategoryID, 'gibbonPersonID' => $gibbonPersonID, 'choiceIDs' => $choiceIDs];
        $sql = "DELETE FROM gibbonActivityChoice 
                WHERE gibbonActivityCategoryID=:gibbonActivityCategoryID 
                AND gibbonPersonID=:gibbonPersonID
                AND NOT FIND_IN_SET(gibbonActivityChoiceID, :choiceIDs)";

        return $this->db()->delete($sql, $data);
    }

}
