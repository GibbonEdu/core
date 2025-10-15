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

namespace Gibbon\Domain\Calendar;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryableGateway;

/**
 * @version v29
 * @since   v29
 */
class CalendarEventPersonGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonCalendarEventPerson';
    private static $primaryKey = 'gibbonCalendarEventPersonID';

    private static $searchableColumns = [];

    public function queryEnrolledStudents($criteria, $gibbonCalendarEventID, $gibbonSchoolYearID) {
        $query = $this
            ->newQuery()
            ->cols(['gibbonCalendarEventPerson.*', 'surname', 'preferredName', 'gibbonFormGroup.nameShort as formGroup'])
            ->from($this->getTableName())
            ->innerJoin('gibbonCalendarEvent', 'gibbonCalendarEvent.gibbonCalendarEventID=gibbonCalendarEventPerson.gibbonCalendarEventID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonCalendarEventPerson.gibbonPersonID')
            ->leftJoin('gibbonStudentEnrolment', 'gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->leftJoin('gibbonFormGroup', 'gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID')
            ->where('gibbonCalendarEventPerson.gibbonCalendarEventID = :gibbonCalendarEventID')
            ->bindValue('gibbonCalendarEventID', $gibbonCalendarEventID)
            ->where('gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonPerson.status="Full"')
            ->where('gibbonCalendarEventPerson.role = :role')
            ->bindValue('role', 'Attendee');

        return $this->runQuery($query, $criteria);
    }
    public function selectEventStaff($gibbonCalendarEventID) {
        $select = $this
            ->newSelect()
            ->cols(['preferredName, surname, gibbonCalendarEventPerson.*'])
            ->from($this->getTableName())
            ->leftJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonCalendarEventPerson.gibbonPersonID')
            ->where('gibbonCalendarEventPerson.gibbonCalendarEventID = :gibbonCalendarEventID')
            ->bindValue('gibbonCalendarEventID', $gibbonCalendarEventID)
            ->where('gibbonCalendarEventPerson.role != :role')
            ->bindValue('role', 'Attendee')
            ->orderBy(['surname', 'preferredName']);

        return $this->runSelect($select);
    }

     public function selectAllParticipants($gibbonCalendarEventID) {
        $select = $this
            ->newSelect()
            ->cols(['preferredName, surname, gibbonCalendarEventPerson.*'])
            ->from($this->getTableName())
            ->leftJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonCalendarEventPerson.gibbonPersonID')
            ->where('gibbonCalendarEventPerson.gibbonCalendarEventID = :gibbonCalendarEventID')
            ->bindValue('gibbonCalendarEventID', $gibbonCalendarEventID)
            ->orderBy(['role','surname', 'preferredName']);

        return $this->runSelect($select);
    }

    public function selectTargetStudentsForEnrolment($gibbonSchoolYearID, $targetStudents, $targetID)
    {
        switch ($targetStudents) {
            case 'Activity':
                $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonActivityID' => $targetID];
                $sql = "SELECT gibbonPerson.gibbonPersonID
                        FROM gibbonStudentEnrolment
                        JOIN gibbonPerson ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                        JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID)
                        WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                        AND gibbonActivityStudent.gibbonActivityID=:gibbonActivityID
                        AND gibbonActivityStudent.status='Accepted'
                        AND gibbonPerson.status='Full'
                        ORDER BY gibbonPerson.surname, gibbonPerson.preferredName";
                    break;
            case 'Messenger':
                $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonGroupID' => $targetID];
                $sql = "SELECT gibbonPerson.gibbonPersonID
                        FROM gibbonStudentEnrolment 
                        JOIN gibbonPerson ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                        JOIN gibbonGroupPerson ON (gibbonGroupPerson.gibbonPersonID=gibbonPerson.gibbonPersonID)
                        WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                        AND gibbonGroupPerson.gibbonGroupID=:gibbonGroupID
                        AND gibbonPerson.status='Full' 
                        ORDER BY gibbonPerson.surname, gibbonPerson.preferredName";
                    break;
            case 'manualSelect':
                $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonIDList' => implode(',', $targetID)];
                $sql = "SELECT gibbonPerson.gibbonPersonID
                        FROM gibbonStudentEnrolment
                        JOIN gibbonPerson ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) 
                        WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                        AND FIND_IN_SET(gibbonPerson.gibbonPersonID, :gibbonPersonIDList)
                        AND gibbonPerson.status='Full' 
                        ORDER BY gibbonPerson.surname, gibbonPerson.preferredName";
                break;
        }

        return $this->db()->select($sql, $data);
    }
}