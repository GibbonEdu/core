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
use Gibbon\Services\Format;

/**
 * Activity Gateway
 *
 * @version v16
 * @since   v16
 */
class ActivityGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonActivity';
    private static $primaryKey = 'gibbonActivityID';

    private static $searchableColumns = ['gibbonActivity.name', 'gibbonActivity.type'];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryActivitiesBySchoolYear(QueryCriteria $criteria, $gibbonSchoolYearID, $dateType = null, $gibbonYearGroupID = null)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonActivity.gibbonActivityID', 'gibbonActivity.name', 'gibbonActivity.active', 'gibbonActivity.provider', 'gibbonActivity.registration', 'gibbonActivity.type', 'gibbonSchoolYearTermIDList', 'programStart', 'programEnd', 'payment', 'paymentType', 'paymentFirmness', 'maxParticipants',
                'gibbonActivityType.access', 'gibbonActivityType.maxPerStudent', 'gibbonActivityType.waitingList',
                "(CASE WHEN gibbonActivity.registration = 'Y' THEN '0' ELSE '1' END) AS registrationOrder",
                "GROUP_CONCAT(DISTINCT gibbonYearGroup.nameShort ORDER BY gibbonYearGroup.sequenceNumber SEPARATOR ', ') as yearGroups",
                "COUNT(DISTINCT gibbonYearGroup.gibbonYearGroupID) as yearGroupCount",
                "COUNT(DISTINCT CASE WHEN gibbonActivityStudent.status = 'Accepted' THEN gibbonActivityStudent.gibbonPersonID END) as enrolment",
                "COUNT(DISTINCT CASE WHEN gibbonActivityStudent.status = 'Waiting List' THEN gibbonActivityStudent.gibbonPersonID END) as waiting",
                "COUNT(DISTINCT CASE WHEN gibbonActivityStudent.status = 'Pending' THEN gibbonActivityStudent.gibbonPersonID END) as pending",
                "gibbonActivityPhoto.filePath as headerImage"
            ])
            ->leftJoin('gibbonYearGroup', 'FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonActivity.gibbonYearGroupIDList)')
            ->leftJoin('gibbonActivityType', 'gibbonActivity.type=gibbonActivityType.name')
            ->leftJoin('gibbonActivityPhoto', 'gibbonActivity.gibbonActivityID=gibbonActivityPhoto.gibbonActivityID AND gibbonActivityPhoto.sequenceNumber=0')
            ->leftJoin('gibbonActivityStudent', 'gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID')
            ->where('gibbonActivity.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->groupBy(['gibbonActivity.gibbonActivityID']);


        if (!empty($gibbonYearGroupID)) {
            $query->where('FIND_IN_SET(:gibbonYearGroupID, gibbonActivity.gibbonYearGroupIDList)')
                  ->bindValue('gibbonYearGroupID', $gibbonYearGroupID);
            $query->where("gibbonActivity.active = 'Y'");
            $query->where("gibbonActivityType.access <> 'None'");
        }

        if ($dateType == 'Term') {
            $query->where("NOT gibbonSchoolYearTermIDList=''");
        } else if ($dateType == 'Date') {
            $query->where('listingStart<=:today AND listingEnd>=:today')
                  ->bindValue('today', date('Y-m-d'));
        }

        $criteria->addFilterRules([
            'term' => function ($query, $gibbonSchoolYearTermID) {
                return $query
                    ->where('FIND_IN_SET(:gibbonSchoolYearTermID, gibbonActivity.gibbonSchoolYearTermIDList)')
                    ->bindValue('gibbonSchoolYearTermID', $gibbonSchoolYearTermID);
            },
            'active' => function ($query, $active) {
                return $query
                    ->where('gibbonActivity.active = :active')
                    ->bindValue('active', $active);
            },
            'category' => function ($query, $gibbonActivityCategoryID) {
                return $query
                    ->where('gibbonActivity.gibbonActivityCategoryID = :gibbonActivityCategoryID')
                    ->bindValue('gibbonActivityCategoryID', $gibbonActivityCategoryID);
            },
            'registration' => function ($query, $registration) {
                return $query
                    ->where('gibbonActivity.registration = :registration')
                    ->bindValue('registration', $registration);
            },
            'enrolment' => function ($query, $enrolment) {
                if ($enrolment == 'less') $query->having('enrolment < gibbonActivity.maxParticipants AND gibbonActivity.maxParticipants > 0');
                if ($enrolment == 'full') $query->having('enrolment = gibbonActivity.maxParticipants AND gibbonActivity.maxParticipants > 0');
                if ($enrolment == 'greater') $query->having('enrolment > gibbonActivity.maxParticipants AND gibbonActivity.maxParticipants > 0');
                return $query;
            },
            'status' => function ($query, $status) {
                if ($status == 'waiting') $query->having('waiting > 0');
                if ($status == 'pending') $query->having('pending > 0');
                return $query;
            },
            'yearGroup' => function ($query, $gibbonYearGroupID) {
                return $query
                    ->where('FIND_IN_SET(:gibbonYearGroupID, gibbonActivity.gibbonYearGroupIDList)')
                    ->bindValue('gibbonYearGroupID', $gibbonYearGroupID);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function queryActivitiesByParticipant(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonPersonID)
    {
        $query = $this
            ->newQuery()
            ->cols([
                'gibbonActivity.gibbonActivityID', 'gibbonActivityCategory.gibbonActivityCategoryID', 'gibbonActivity.name', 'gibbonActivity.type', 'gibbonActivityStudent.status', 'NULL AS role', 'gibbonActivityCategory.name as category', 'gibbonActivityCategory.sequenceNumber', 'NULL AS choices', 'gibbonActivityCategory.accessOpenDate', 'gibbonActivityCategory.accessCloseDate', 'gibbonActivityCategory.accessEnrolmentDate'
            ])
            ->from($this->getTableName())
            ->innerJoin('gibbonActivityStudent', 'gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID')
            ->leftJoin('gibbonActivityCategory', 'gibbonActivityCategory.gibbonActivityCategoryID=gibbonActivity.gibbonActivityCategoryID')
            ->where('gibbonActivity.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonActivityStudent.gibbonPersonID = :gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->where('gibbonActivity.active="Y"')
            ->where('(gibbonActivityCategory.gibbonActivityCategoryID IS NULL OR ((CURRENT_TIMESTAMP >= gibbonActivityCategory.accessEnrolmentDate AND CURRENT_TIMESTAMP >= gibbonActivityCategory.viewableDate) AND gibbonActivityCategory.active="Y"))');

        $query->unionAll()
            ->cols([
                'gibbonActivity.gibbonActivityID', 'gibbonActivityCategory.gibbonActivityCategoryID', 'gibbonActivity.name', 'gibbonActivity.type', 'NULL AS status', 'gibbonActivityStaff.role AS role', 'gibbonActivityCategory.name as category', 'gibbonActivityCategory.sequenceNumber', 'NULL AS choices', 'gibbonActivityCategory.accessOpenDate', 'gibbonActivityCategory.accessCloseDate', 'gibbonActivityCategory.accessEnrolmentDate'
            ])
            ->from($this->getTableName())
            ->innerJoin('gibbonActivityStaff', 'gibbonActivityStaff.gibbonActivityID=gibbonActivity.gibbonActivityID')
            ->leftJoin('gibbonActivityCategory', 'gibbonActivityCategory.gibbonActivityCategoryID=gibbonActivity.gibbonActivityCategoryID')
            ->where('gibbonActivity.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonActivityStaff.gibbonPersonID = :gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->where('gibbonActivity.active="Y" AND gibbonActivityCategory.active="Y"')
            ->where('gibbonActivityCategory.viewableDate IS NOT NULL')
            ->groupBy(['gibbonActivityCategory.gibbonActivityCategoryID', 'gibbonActivity.gibbonActivityID']);

        $query->unionAll()
            ->cols([
                'NULL as gibbonActivityID', 'gibbonActivityCategory.gibbonActivityCategoryID', "NULL as name", 'NULL as type', '"Pending" as status', 'NULL AS role', 'gibbonActivityCategory.name as category', 'gibbonActivityCategory.sequenceNumber', "GROUP_CONCAT(gibbonActivity.name ORDER BY gibbonActivityChoice.choice SEPARATOR ',') AS choices", 'gibbonActivityCategory.accessOpenDate', 'gibbonActivityCategory.accessCloseDate', 'gibbonActivityCategory.accessEnrolmentDate'
            ])
            ->from('gibbonActivityCategory')
            ->innerJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonSchoolYearID=gibbonActivityCategory.gibbonSchoolYearID')
            ->leftJoin('gibbonActivityChoice', 'gibbonActivityChoice.gibbonActivityCategoryID=gibbonActivityCategory.gibbonActivityCategoryID AND gibbonActivityChoice.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->leftJoin('gibbonActivity', 'gibbonActivity.gibbonActivityID=gibbonActivityChoice.gibbonActivityID AND gibbonActivity.active="Y"')
            ->leftJoin('gibbonActivityStudent', 'gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID AND gibbonActivityStudent.gibbonPersonID=gibbonActivityChoice.gibbonPersonID')
            ->where('gibbonStudentEnrolment.gibbonPersonID = :gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->where('gibbonStudentEnrolment.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonActivityCategory.active="Y"')
            ->where('(gibbonActivityCategory.accessEnrolmentDate IS NULL OR CURRENT_TIMESTAMP < gibbonActivityCategory.accessEnrolmentDate)')
            ->where('CURRENT_TIMESTAMP >= gibbonActivityCategory.viewableDate')
            ->groupBy(['gibbonActivityCategory.gibbonActivityCategoryID'])
            ;


        return $this->runQuery($query, $criteria);
    }

    public function selectActiveEnrolledActivities($gibbonSchoolYearID, $gibbonPersonID, $dateType, $date = null)
    {
        $query = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols([
                'gibbonActivity.gibbonActivityID', 'gibbonActivity.name', 'gibbonActivity.provider', 'gibbonPerson.gibbonPersonID', 'gibbonActivitySlot.timeStart', 'gibbonActivitySlot.timeEnd', 'gibbonActivitySlot.locationExternal', 'gibbonSpace.name as space', 'gibbonDaysOfWeek.name as dayOfWeek',
            ])
            ->innerJoin('gibbonActivitySlot', 'gibbonActivitySlot.gibbonActivityID=gibbonActivity.gibbonActivityID')
            ->innerJoin('gibbonActivityCategory', 'gibbonActivityCategory.gibbonActivityCategoryID=gibbonActivity.gibbonActivityCategoryID')
            ->innerJoin('gibbonDaysOfWeek', 'gibbonActivitySlot.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID')
            ->innerJoin('gibbonActivityStudent', 'gibbonActivity.gibbonActivityID=gibbonActivityStudent.gibbonActivityID')
            ->innerJoin('gibbonPerson', "gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID")
            ->leftJoin('gibbonSpace', 'gibbonSpace.gibbonSpaceID=gibbonActivitySlot.gibbonSpaceID')
            ->where('gibbonActivity.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where("gibbonActivity.active = 'Y'")
            ->where("gibbonActivityStudent.status='Accepted'")
            ->where("gibbonPerson.status = 'Full'")
            ->where('(dateStart IS NULL OR dateStart<=:today)')
            ->where('(dateEnd IS NULL OR dateEnd>=:today)')
            ->bindValue('today', $date ?? date('Y-m-d'))
            ->where('gibbonActivityStudent.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->bindValue('dateType', $dateType)
            ->where('CURRENT_TIMESTAMP >= gibbonActivityCategory.accessEnrolmentDate');

        if ($dateType == 'Term') {
            $query->cols(['gibbonSchoolYearTerm.firstDay as dateStart', 'gibbonSchoolYearTerm.lastDay as dateEnd'])
                ->innerJoin('gibbonSchoolYearTerm', "FIND_IN_SET(gibbonSchoolYearTermID, gibbonActivity.gibbonSchoolYearTermIDList)");
        } else {
            $query->cols(['gibbonActivity.programStart as dateStart', 'gibbonActivity.programEnd as dateEnd']);
        }

        $query->unionAll()
            ->from($this->getTableName())
            ->cols([
                'gibbonActivity.gibbonActivityID', 'gibbonActivity.name', 'gibbonActivity.provider', 'gibbonPerson.gibbonPersonID', 'gibbonActivitySlot.timeStart', 'gibbonActivitySlot.timeEnd', 'gibbonActivitySlot.locationExternal', 'gibbonSpace.name as space', 'gibbonDaysOfWeek.name as dayOfWeek',
            ])
            ->innerJoin('gibbonActivitySlot', 'gibbonActivitySlot.gibbonActivityID=gibbonActivity.gibbonActivityID')
            ->innerJoin('gibbonDaysOfWeek', 'gibbonActivitySlot.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID')
            ->innerJoin('gibbonActivityStaff', 'gibbonActivity.gibbonActivityID=gibbonActivityStaff.gibbonActivityID')
            ->innerJoin('gibbonPerson', "gibbonActivityStaff.gibbonPersonID=gibbonPerson.gibbonPersonID")
            ->leftJoin('gibbonSpace', 'gibbonSpace.gibbonSpaceID=gibbonActivitySlot.gibbonSpaceID')
            ->where('gibbonActivity.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where("gibbonActivity.active = 'Y'")
            ->where("gibbonPerson.status = 'Full'")
            ->where('(dateStart IS NULL OR dateStart<=:today)')
            ->where('(dateEnd IS NULL OR dateEnd>=:today)')
            ->bindValue('today', $date ?? date('Y-m-d'))
            ->where('gibbonActivityStaff.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->bindValue('dateType', $dateType);

        if ($dateType == 'Term') {
            $query->cols(['gibbonSchoolYearTerm.firstDay as dateStart', 'gibbonSchoolYearTerm.lastDay as dateEnd'])
                ->innerJoin('gibbonSchoolYearTerm', "FIND_IN_SET(gibbonSchoolYearTermID, gibbonActivity.gibbonSchoolYearTermIDList)");
        } else {
            $query->cols(['gibbonActivity.programStart as dateStart', 'gibbonActivity.programEnd as dateEnd']);
        }

        return $this->runSelect($query);
    }

    public function selectActivitiesByFacility($gibbonSchoolYearID, $gibbonSpaceID, $dateType)
    {
        $query = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols([
                'gibbonActivity.gibbonActivityID', 'gibbonActivity.name', 'gibbonActivity.provider', 'gibbonSpace.gibbonSpaceID', 'gibbonActivitySlot.timeStart', 'gibbonActivitySlot.timeEnd', 'gibbonActivitySlot.locationExternal', 'gibbonSpace.name as space', 'gibbonDaysOfWeek.name as dayOfWeek',
            ])
            ->innerJoin('gibbonActivitySlot', 'gibbonActivitySlot.gibbonActivityID=gibbonActivity.gibbonActivityID')
            ->innerJoin('gibbonDaysOfWeek', 'gibbonActivitySlot.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID')
            ->leftJoin('gibbonSpace', 'gibbonSpace.gibbonSpaceID=gibbonActivitySlot.gibbonSpaceID')
            ->where('gibbonActivity.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where("gibbonActivity.active = 'Y'")
            ->where('gibbonSpace.gibbonSpaceID=:gibbonSpaceID')
            ->bindValue('gibbonSpaceID', $gibbonSpaceID);

        if ($dateType == 'Term') {
            $query->cols(['gibbonSchoolYearTerm.firstDay as dateStart', 'gibbonSchoolYearTerm.lastDay as dateEnd'])
                ->innerJoin('gibbonSchoolYearTerm', "FIND_IN_SET(gibbonSchoolYearTermID, gibbonActivity.gibbonSchoolYearTermIDList)");
        } else {
            $query->cols(['gibbonActivity.programStart as dateStart', 'gibbonActivity.programEnd as dateEnd']);
        }

        return $this->runSelect($query);
    }

    public function selectActivityEnrolmentByStudent($gibbonSchoolYearID, $gibbonPersonID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID);
        $sql = "SELECT gibbonActivity.gibbonActivityID AS groupBy, gibbonActivityStudent.* FROM gibbonActivityStudent 
                JOIN gibbonActivity ON (gibbonActivity.gibbonActivityID=gibbonActivityStudent.gibbonActivityID)
                JOIN gibbonActivityCategory ON (gibbonActivityCategory.gibbonActivityCategoryID=gibbonActivity.gibbonActivityCategoryID)
                WHERE gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID
                AND gibbonActivityStudent.gibbonPersonID=:gibbonPersonID
                AND CURRENT_TIMESTAMP >= gibbonActivityCategory.accessEnrolmentDate";

        return $this->db()->select($sql, $data);
    }

    public function selectWeekdayNamesByActivity($gibbonActivityID)
    {
        $data = array('gibbonActivityID' => $gibbonActivityID);
        $sql = "SELECT DISTINCT nameShort 
                FROM gibbonActivitySlot 
                JOIN gibbonDaysOfWeek ON (gibbonActivitySlot.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID) 
                WHERE gibbonActivityID=:gibbonActivityID 
                ORDER BY sequenceNumber";

        return $this->db()->select($sql, $data);
    }

    public function selectActivityTypeOptions()
    {
        $sql = "SELECT name as value, name FROM gibbonActivityType ORDER BY name";

        return $this->db()->select($sql);
    }
    
    public function selectActivitiesBySchoolYear($gibbonSchoolYearID)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID];
        $sql = "SELECT gibbonActivity.gibbonActivityID AS value, name FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ORDER BY name, programStart";

        return $this->db()->select($sql, $data);
    }

    public function selectActivitiesByCategoryAndSchoolYear($gibbonSchoolYearID)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID];
        $sql = "SELECT gibbonActivity.gibbonActivityCategoryID, gibbonActivity.gibbonActivityID, name FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ORDER BY name, programStart";

        return $this->db()->select($sql, $data);
    }

    public function selectActivitiesByCategory($gibbonActivityCategoryID)
    {
        $data = ['gibbonActivityCategoryID' => $gibbonActivityCategoryID];
        $sql = "SELECT gibbonActivity.gibbonActivityID as value, name FROM gibbonActivity WHERE gibbonActivityCategoryID=:gibbonActivityCategoryID AND active='Y' ORDER BY name, programStart";

        return $this->db()->select($sql, $data);
    }

    public function selectActivitiesByCategoryAndPerson($gibbonActivityCategoryID, $gibbonPersonID)
    {
        $data = ['gibbonActivityCategoryID' => $gibbonActivityCategoryID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT gibbonActivity.gibbonActivityID AS value, gibbonActivity.name 
                FROM gibbonActivity
                JOIN gibbonYearGroup ON (FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonActivity.gibbonYearGroupIDList))
                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonActivity.gibbonSchoolYearID AND gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
                WHERE gibbonActivity.gibbonActivityCategoryID=:gibbonActivityCategoryID 
                AND gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonID
                AND gibbonActivity.active='Y'
                ORDER BY gibbonActivity.name, gibbonActivity.programStart";

        return $this->db()->select($sql, $data);
    }

    public function getActivityDetailsByID($gibbonActivityID)
    {
        $data = ['gibbonActivityID' => $gibbonActivityID];
        $sql = "SELECT gibbonActivity.*,
                gibbonActivity.payment as cost,
                gibbonActivity.paymentType as costType,
                gibbonActivity.paymentFirmness as costStatus,
                gibbonActivityType.access,
                gibbonActivityType.maxPerStudent,
                gibbonActivityType.enrolmentType,
                gibbonActivityType.backupChoice,
                gibbonSpace.gibbonSpaceID,
                gibbonActivitySlot.timeStart,
                gibbonActivitySlot.timeEnd,
                gibbonActivitySlot.locationExternal,
                gibbonSpace.name as space,
                gibbonDaysOfWeek.name as dayOfWeek,
                GROUP_CONCAT(DISTINCT gibbonYearGroup.nameShort ORDER BY gibbonYearGroup.sequenceNumber SEPARATOR ', ') as yearGroups,
                COUNT(DISTINCT gibbonYearGroup.gibbonYearGroupID) as yearGroupCount
            FROM gibbonActivity 
            LEFT JOIN gibbonYearGroup ON (FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonActivity.gibbonYearGroupIDList))
            LEFT JOIN gibbonActivityType ON (gibbonActivity.type=gibbonActivityType.name)
            LEFT JOIN gibbonActivitySlot ON (gibbonActivity.gibbonActivityID=gibbonActivitySlot.gibbonActivityID)
            LEFT JOIN gibbonDaysOfWeek ON (gibbonActivitySlot.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID)
            LEFT JOIN gibbonSpace ON (gibbonSpace.gibbonSpaceID=gibbonActivitySlot.gibbonSpaceID)
            WHERE gibbonActivity.gibbonActivityID=:gibbonActivityID
            GROUP BY gibbonActivity.gibbonActivityID";

        return $this->db()->selectOne($sql, $data);
    }

    public function selectActivityDetailsByCategory($gibbonActivityCategoryID)
    {
        $data = ['gibbonActivityCategoryID' => $gibbonActivityCategoryID];
        $sql = "SELECT gibbonActivity.gibbonActivityID as groupBy, 
                gibbonActivity.*,
                gibbonSpace.gibbonSpaceID,
                gibbonActivitySlot.timeStart,
                gibbonActivitySlot.timeEnd,
                gibbonActivitySlot.locationExternal,
                gibbonSpace.name as space,
                gibbonDaysOfWeek.name as dayOfWeek,
                COUNT(DISTINCT gibbonActivityStudent.gibbonActivityStudentID) as enrolmentCount,
                GROUP_CONCAT(DISTINCT gibbonYearGroup.nameShort ORDER BY gibbonYearGroup.sequenceNumber SEPARATOR ', ') as yearGroups,
                COUNT(DISTINCT gibbonYearGroup.gibbonYearGroupID) as yearGroupCount
            FROM gibbonActivity 
            LEFT JOIN gibbonYearGroup ON (FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonActivity.gibbonYearGroupIDList))
            LEFT JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID AND gibbonActivityStudent.status='Accepted')
            LEFT JOIN gibbonActivityType ON (gibbonActivity.type=gibbonActivityType.name)
            LEFT JOIN gibbonActivitySlot ON (gibbonActivity.gibbonActivityID=gibbonActivitySlot.gibbonActivityID)
            LEFT JOIN gibbonDaysOfWeek ON (gibbonActivitySlot.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID)
            LEFT JOIN gibbonSpace ON (gibbonSpace.gibbonSpaceID=gibbonActivitySlot.gibbonSpaceID)
            WHERE gibbonActivity.gibbonActivityCategoryID=:gibbonActivityCategoryID
            AND gibbonActivity.active='Y'
            GROUP BY gibbonActivity.gibbonActivityID";

        return $this->db()->select($sql, $data);
    }

    function getStudentActivityCountByType($type, $gibbonPersonID)
    {
        $data = array('gibbonPersonID' => $gibbonPersonID, 'type' => $type, 'date' => date('Y-m-d'));
        $sql = "SELECT COUNT(*) 
                FROM gibbonActivity 
                JOIN gibbonActivityStudent ON (gibbonActivity.gibbonActivityID=gibbonActivityStudent.gibbonActivityID) 
                JOIN gibbonSchoolYear ON (gibbonSchoolYear.gibbonSchoolYearID=gibbonActivity.gibbonSchoolYearID)
                WHERE gibbonActivityStudent.gibbonPersonID=:gibbonPersonID 
                AND gibbonActivityStudent.status='Accepted' 
                AND gibbonActivity.type=:type
                AND gibbonActivity.active='Y'
                AND :date BETWEEN gibbonSchoolYear.firstDay AND gibbonSchoolYear.lastDay";
        return $this->db()->selectOne($sql, $data);
    }

    function getOverlappingActivityTimeSlot($gibbonActivityID, $gibbonPersonID, $dateType)
    {
        $data = ['gibbonActivityID' => $gibbonActivityID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT existingActivity.gibbonActivityID as id, existingActivity.name
                    FROM gibbonActivity as sourceActivity
                    JOIN gibbonActivitySlot as sourceSlot ON (sourceActivity.gibbonActivityID=sourceSlot.gibbonActivityID)
                    JOIN gibbonActivity as existingActivity ON (existingActivity.gibbonSchoolYearID=sourceActivity.gibbonSchoolYearID)
                    LEFT JOIN gibbonActivitySlot as existingSlot ON (existingActivity.gibbonActivityID=existingSlot.gibbonActivityID)
                    LEFT JOIN gibbonActivityStudent as existingEnrolment ON (existingActivity.gibbonActivityID=existingEnrolment.gibbonActivityID AND existingEnrolment.gibbonPersonID=:gibbonPersonID ) 
                WHERE sourceActivity.gibbonActivityID=:gibbonActivityID
                    AND existingEnrolment.status='Accepted' 
                    AND existingActivity.active='Y'
                    AND existingSlot.gibbonDaysOfWeekID=sourceSlot.gibbonDaysOfWeekID
                    AND (
                        (existingSlot.timeStart >= sourceSlot.timeStart AND existingSlot.timeStart < sourceSlot.timeEnd) OR
                        (sourceSlot.timeStart >= existingSlot.timeStart AND sourceSlot.timeStart < existingSlot.timeEnd)
                    )
                ";

        if ($dateType == 'Date') {
            $sql .= "AND (
                (existingActivity.programStart >= sourceActivity.programStart AND existingActivity.programStart < sourceActivity.programEnd) OR
                (sourceActivity.programStart >= existingActivity.programStart AND sourceActivity.programStart < existingActivity.programEnd)
            )";
        } else if ($dateType == 'Term') {
            $sql .= "AND sourceActivity.gibbonSchoolYearTermIDList LIKE CONCAT('%',existingActivity.gibbonSchoolYearTermIDList,'%')";
        }

        return $this->db()->select($sql, $data);
    }

    public function getActivitySignUpAccess($gibbonActivityID, $gibbonPersonID)
    {
        $data = ['gibbonActivityID' => $gibbonActivityID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT gibbonStudentEnrolment.gibbonStudentEnrolmentID
                FROM gibbonActivity
                JOIN gibbonActivityCategory ON (gibbonActivity.gibbonActivityCategoryID=gibbonActivityCategory.gibbonActivityCategoryID)
                JOIN gibbonYearGroup ON (FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonActivity.gibbonYearGroupIDList))
                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonActivityCategory.gibbonSchoolYearID AND gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
                WHERE gibbonActivity.gibbonActivityID=:gibbonActivityID
                AND gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonID
                GROUP BY gibbonActivity.gibbonActivityID";

        return $this->db()->selectOne($sql, $data);
    }

    public function getNextActivityByID($gibbonActivityCategoryID, $gibbonActivityID)
    {
        $data = ['gibbonActivityCategoryID' => $gibbonActivityCategoryID, 'gibbonActivityID' => $gibbonActivityID];
        $sql = "SELECT * FROM gibbonActivity WHERE name=(SELECT MIN(name) FROM gibbonActivity WHERE name > (SELECT name FROM gibbonActivity WHERE gibbonActivityID=:gibbonActivityID AND gibbonActivityCategoryID=:gibbonActivityCategoryID) AND gibbonActivityCategoryID=:gibbonActivityCategoryID) AND gibbonActivityCategoryID=:gibbonActivityCategoryID";

        return $this->db()->selectOne($sql, $data);
    }

    public function getPreviousActivityByID($gibbonActivityCategoryID, $gibbonActivityID)
    {
        $data = ['gibbonActivityCategoryID' => $gibbonActivityCategoryID, 'gibbonActivityID' => $gibbonActivityID];
        $sql = "SELECT * FROM gibbonActivity WHERE name=(SELECT MAX(name) FROM gibbonActivity WHERE name < (SELECT name FROM gibbonActivity WHERE gibbonActivityID=:gibbonActivityID AND gibbonActivityCategoryID=:gibbonActivityCategoryID) AND gibbonActivityCategoryID=:gibbonActivityCategoryID) AND gibbonActivityCategoryID=:gibbonActivityCategoryID";

        return $this->db()->selectOne($sql, $data);
    }

}
