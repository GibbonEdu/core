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
 * @version v17
 * @since   v17
 */
class ActivityReportGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonActivity';

    private static $searchableColumns = ['gibbonActivity.name', 'gibbonActivity.type'];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryActivityEnrollmentSummary(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonActivity.gibbonActivityID', 'gibbonActivity.name', 'gibbonActivity.active', 'gibbonActivity.provider', 'gibbonActivity.registration', 'gibbonActivity.type', 'maxParticipants',
                "COUNT(DISTINCT CASE WHEN gibbonActivityStudent.status = 'Accepted' THEN gibbonActivityStudent.gibbonPersonID END) as enrolment",
                "COUNT(DISTINCT CASE WHEN gibbonActivityStudent.status <> 'Not Accepted' THEN gibbonActivityStudent.gibbonPersonID END) as registered",
            ])
            ->leftJoin('gibbonActivityStudent', 'gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID')
            ->leftJoin('gibbonPerson', "gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonPerson.status = 'Full'")
            ->leftJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL OR dateEnd>=:today)')
            ->bindValue('today', date('Y-m-d'))
            ->where('gibbonActivity.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->groupBy(['gibbonActivity.gibbonActivityID']);

        $criteria->addFilterRules([
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
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function selectActivitySpreadByStudent($gibbonSchoolYearID, $gibbonPersonID, $dateType, $status = 'Accepted')
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols($dateType == 'Term' 
                ? ["CONCAT(gibbonSchoolYearTerm.gibbonSchoolYearTermID, '-', gibbonActivitySlot.gibbonDaysOfWeekID) AS groupBy"]
                : ['gibbonActivitySlot.gibbonDaysOfWeekID AS groupBy'] )
            ->cols([
                'gibbonActivityStudent.gibbonPersonID', 
                'COUNT(DISTINCT gibbonActivityStudent.gibbonActivityStudentID) AS count', 
                "COUNT(DISTINCT CASE WHEN gibbonActivityStudent.status<>'Accepted' THEN gibbonActivityStudent.gibbonActivityStudentID END) AS notAccepted", 
                "GROUP_CONCAT(DISTINCT gibbonActivity.name SEPARATOR ', ') AS activityNames"
            ])
            ->innerJoin('gibbonActivityStudent', 'gibbonActivity.gibbonActivityID=gibbonActivityStudent.gibbonActivityID')
            ->innerJoin('gibbonActivitySlot', 'gibbonActivitySlot.gibbonActivityID=gibbonActivity.gibbonActivityID')
            ->where('gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonActivityStudent.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID);

        if ($status == 'Accepted') {
            $query->where("gibbonActivityStudent.status='Accepted'");
        } else {
            $query->where("gibbonActivityStudent.status<>'Not Accepted'");
        }

        if ($dateType == 'Term') {
            $query->innerJoin('gibbonSchoolYearTerm', 'FIND_IN_SET(gibbonSchoolYearTerm.gibbonSchoolYearTermID, gibbonActivity.gibbonSchoolYearTermIDList)')
                ->groupBy(['gibbonSchoolYearTerm.gibbonSchoolYearTermID', 'gibbonActivitySlot.gibbonDaysOfWeekID']);
        } else {
            $query->groupBy(['gibbonActivitySlot.gibbonDaysOfWeekID']);
        }

        return $this->db()->select($query->getStatement(), $query->getBindValues());
    }

    public function selectActivityWeekdays($gibbonSchoolYearID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT gibbonDaysOfWeek.*
                FROM gibbonDaysOfWeek 
                JOIN gibbonActivitySlot ON (gibbonActivitySlot.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID) 
                JOIN gibbonActivity ON (gibbonActivitySlot.gibbonActivityID=gibbonActivity.gibbonActivityID) 
                WHERE gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID AND schoolDay='Y' 
                GROUP BY gibbonDaysOfWeek.gibbonDaysOfWeekID
                ORDER BY gibbonDaysOfWeek.sequenceNumber";

        return $this->db()->select($sql, $data);
    }

    public function selectActivityWeekdaysPerTerm($gibbonSchoolYearID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT gibbonSchoolYearTerm.name, gibbonDaysOfWeek.*, gibbonSchoolYearTerm.name as termName, gibbonSchoolYearTerm.gibbonSchoolYearTermID as gibbonSchoolYearTermID
                FROM gibbonDaysOfWeek 
                JOIN gibbonActivitySlot ON (gibbonActivitySlot.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID) 
                JOIN gibbonActivity ON (gibbonActivitySlot.gibbonActivityID=gibbonActivity.gibbonActivityID) 
                JOIN gibbonSchoolYearTerm ON (gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonActivity.gibbonSchoolYearID)
                WHERE gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID AND schoolDay='Y' 
                GROUP BY gibbonDaysOfWeek.gibbonDaysOfWeekID, gibbonSchoolYearTerm.gibbonSchoolYearTermID
                ORDER BY gibbonSchoolYearTerm.sequenceNumber, gibbonDaysOfWeek.sequenceNumber";

        return $this->db()->select($sql, $data);
    }
}
