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
            ])
            ->leftJoin('gibbonYearGroup', 'FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonActivity.gibbonYearGroupIDList)')
            ->leftJoin('gibbonActivityType', 'gibbonActivity.type=gibbonActivityType.name')
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
            ->from($this->getTableName())
            ->cols([
                'gibbonActivity.gibbonActivityID', 'gibbonActivity.name', 'gibbonActivity.active', 'gibbonActivity.type', 'gibbonActivityStudent.status', 'NULL AS role'
            ])
            ->innerJoin('gibbonActivityStudent', 'gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID')
            ->where('gibbonActivity.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonActivityStudent.gibbonPersonID = :gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID);

        $query->unionAll()
            ->from($this->getTableName())
            ->cols([
                'gibbonActivity.gibbonActivityID', 'gibbonActivity.name', 'gibbonActivity.active', 'gibbonActivity.type', 'NULL AS status', 'gibbonActivityStaff.role AS role'
            ])
            ->innerJoin('gibbonActivityStaff', 'gibbonActivityStaff.gibbonActivityID=gibbonActivity.gibbonActivityID')
            ->where('gibbonActivity.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonActivityStaff.gibbonPersonID = :gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID);

        return $this->runQuery($query, $criteria);
    }

    public function selectActivityEnrolmentByStudent($gibbonSchoolYearID, $gibbonPersonID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID);
        $sql = "SELECT gibbonActivity.gibbonActivityID AS groupBy, gibbonActivityStudent.* FROM gibbonActivityStudent 
                JOIN gibbonActivity ON (gibbonActivity.gibbonActivityID=gibbonActivityStudent.gibbonActivityID)
                WHERE gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID
                AND gibbonActivityStudent.gibbonPersonID=:gibbonPersonID";

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
        $this->db()->selectOne($sql, $data);
    }

}
