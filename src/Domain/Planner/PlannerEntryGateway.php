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

namespace Gibbon\Domain\Planner;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * Planner Entry Gateway
 *
 * @version v17
 * @since   v17
 */
class PlannerEntryGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonPlannerEntry';
    private static $primaryKey = 'gibbonPlannerEntryID';
    private static $searchableColumns = [];
    

    public function queryPlannerByClass($criteria, $gibbonSchoolYearID, $gibbonPersonID, $gibbonCourseClassID, $viewingAs = 'Student')
    {
        $cols = ['gibbonPlannerEntry.gibbonPlannerEntryID', 'gibbonPlannerEntry.gibbonUnitID', 'gibbonUnit.name as unit', 'gibbonPlannerEntry.gibbonCourseClassID', 'gibbonCourse.nameShort AS course', 'gibbonCourseClass.nameShort AS class', 'gibbonPlannerEntry.name as lesson', 'timeStart', 'timeEnd', 'viewableStudents', 'viewableParents', 'homework', 'homeworkSubmission', 'homeworkCrowdAssess', 'date'];

        $query = $this
            ->newQuery()
            ->cols(array_merge($cols, ['GROUP_CONCAT(DISTINCT teacher.gibbonPersonID) AS teacherIDs']))
            ->from('gibbonPlannerEntry')
            ->innerJoin('gibbonCourseClass', 'gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
            ->innerJoin('gibbonCourse', 'gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID')
            ->leftJoin('gibbonUnit', 'gibbonUnit.gibbonUnitID=gibbonPlannerEntry.gibbonUnitID')
            ->leftJoin('gibbonCourseClassPerson as teacher', 'gibbonPlannerEntry.gibbonCourseClassID=teacher.gibbonCourseClassID AND (teacher.role = "Teacher" OR teacher.role="Assistant")')
            ->where('gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID')
            ->bindValue('gibbonCourseClassID', $gibbonCourseClassID)
            ->groupBy(['gibbonPlannerEntry.gibbonPlannerEntryID']);

        if (!empty($gibbonPersonID)) {
            $query->cols(['gibbonCourseClassPerson.role', 'gibbonPlannerEntryStudentHomework.homeworkDueDateTime AS myHomeworkDueDateTime'])
                ->innerJoin('gibbonCourseClassPerson', 'gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID')
                ->where('gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID')
                ->leftJoin('gibbonPlannerEntryStudentHomework', 'gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID')
                ->bindValue('gibbonPersonID', $gibbonPersonID)
                ->where('gibbonCourseClassPerson.role NOT LIKE "%Left"')
                ->where('(gibbonPlannerEntry.timeStart != "" AND gibbonPlannerEntry.timeStart IS NOT NULL)');

            $this->unionAllWithCriteria($query, $criteria)
                ->cols(array_merge($cols, ['gibbonPlannerEntryGuest.role', 'NULL AS myHomeworkDueDateTime', 'NULL as teacherIDs']))
                ->from('gibbonPlannerEntry')
                ->innerJoin('gibbonCourseClass', 'gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
                ->innerJoin('gibbonCourse', 'gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID')
                ->innerJoin('gibbonPlannerEntryGuest', 'gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID')
                ->leftJoin('gibbonUnit', 'gibbonUnit.gibbonUnitID=gibbonPlannerEntry.gibbonUnitID')
                ->where('gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID')
                ->bindValue('gibbonCourseClassID', $gibbonCourseClassID)
                ->where('gibbonPlannerEntryGuest.gibbonPersonID=:gibbonPersonID')
                ->bindValue('gibbonPersonID', $gibbonPersonID);
        } else {
            $query->cols(['NULL as role']);
        }

        if ($viewingAs == 'Parent') {
            $query->having('(role = "Student" AND viewableParents = "Y")');
        } elseif ($viewingAs == 'Student') {
            $query->having('(role = "Student" AND viewableStudents = "Y")');
        }

        return $this->runQuery($query, $criteria);
    }

    public function queryPlannerByDate($criteria, $gibbonSchoolYearID, $gibbonPersonID, $date, $viewingAs = 'Student')
    {
        $cols = ['gibbonPlannerEntry.gibbonPlannerEntryID', 'gibbonPlannerEntry.summary', 'gibbonPlannerEntry.gibbonUnitID', 'gibbonUnit.name as unit', 'gibbonPlannerEntry.gibbonCourseClassID', 'gibbonCourse.nameShort AS course', 'gibbonCourseClass.nameShort AS class', 'gibbonPlannerEntry.name as lesson', 'gibbonPlannerEntry.timeStart', 'gibbonPlannerEntry.timeEnd', 'viewableStudents', 'viewableParents', 'homework', 'homeworkSubmission', 'homeworkCrowdAssess', 'gibbonPlannerEntry.date'];

        $query = $this
            ->newQuery()
            ->cols(array_merge($cols, ['GROUP_CONCAT(DISTINCT teacher.gibbonPersonID) AS teacherIDs']))
            ->from('gibbonPlannerEntry')
            ->innerJoin('gibbonCourseClass', 'gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
            ->innerJoin('gibbonCourse', 'gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID')
            ->leftJoin('gibbonUnit', 'gibbonUnit.gibbonUnitID=gibbonPlannerEntry.gibbonUnitID')
            ->leftJoin('gibbonCourseClassPerson as teacher', 'gibbonPlannerEntry.gibbonCourseClassID=teacher.gibbonCourseClassID AND (teacher.role = "Teacher" OR teacher.role="Assistant")')
            ->where('gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonPlannerEntry.date=:date')
            ->bindValue('date', $date)
            ->groupBy(['gibbonPlannerEntry.gibbonPlannerEntryID']);

        if (!empty($gibbonPersonID)) {
            $query->cols(['gibbonCourseClassPerson.role', 'gibbonPlannerEntryStudentHomework.homeworkDueDateTime AS myHomeworkDueDateTime', 'gibbonTTDayRowClass.gibbonTTDayRowClassID'])
                ->innerJoin('gibbonCourseClassPerson', 'gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID')
                ->leftJoin('gibbonPlannerEntryStudentHomework', 'gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID')

                ->leftJoin('gibbonTTDayDate', 'gibbonTTDayDate.date=gibbonPlannerEntry.date')
                ->leftJoin('gibbonTTDay', 'gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID')
                ->leftJoin('gibbonTTColumnRow', 'gibbonTTColumnRow.gibbonTTColumnID=gibbonTTDay.gibbonTTColumnID AND gibbonTTColumnRow.timeStart=gibbonPlannerEntry.timeStart AND gibbonTTColumnRow.timeEnd=gibbonPlannerEntry.timeEnd')
                ->leftJoin('gibbonTTDayRowClass', 'gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID AND gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID')
                ->leftJoin('gibbonTTDayRowClassException', 'gibbonTTDayRowClassException.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID AND gibbonTTDayRowClassException.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID')

                ->where('gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID')
                ->bindValue('gibbonPersonID', $gibbonPersonID)
                ->where('gibbonCourseClassPerson.role NOT LIKE "%Left"')
                ->where('gibbonTTDayRowClassException.gibbonTTDayRowClassExceptionID IS NULL');

            $this->unionAllWithCriteria($query, $criteria)
                ->cols(array_merge($cols, ['gibbonPlannerEntryGuest.role', 'NULL AS myHomeworkDueDateTime', 'NULL as teacherIDs', 'NULL as gibbonTTDayRowClassID']))
                ->from('gibbonPlannerEntry')
                ->innerJoin('gibbonCourseClass', 'gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
                ->innerJoin('gibbonCourse', 'gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID')
                ->innerJoin('gibbonPlannerEntryGuest', 'gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID')
                ->leftJoin('gibbonUnit', 'gibbonUnit.gibbonUnitID=gibbonPlannerEntry.gibbonUnitID')
                ->where('gibbonPlannerEntry.date=:date')
                ->bindValue('date', $date)
                ->where('gibbonPlannerEntryGuest.gibbonPersonID=:gibbonPersonID')
                ->bindValue('gibbonPersonID', $gibbonPersonID);
        } else {
            $query->cols(['NULL as role']);
        }

        if ($viewingAs == 'Parent') {
            $query->having('(role = "Student" AND viewableParents = "Y")');
        } elseif ($viewingAs == 'Student') {
            $query->having('(role = "Student" AND viewableStudents = "Y")');
        } elseif ($viewingAs == 'Teacher') {
            $query->having('(role = "Teacher")');
        }

        return $this->runQuery($query, $criteria);
    }

    public function queryPlannerTimeSlotsByClass($criteria, $gibbonSchoolYearID, $gibbonCourseClassID)
    {
        $query = $this
            ->newQuery()
            ->cols(['gibbonTTColumnRow.timeStart', 'gibbonTTColumnRow.timeEnd', 'gibbonTTDayDate.date', 'gibbonTTColumnRow.name AS period', 'gibbonTTDayRowClass.gibbonTTDayRowClassID', 'gibbonTTDayRowClass.gibbonCourseClassID', 'gibbonTTDayDate.gibbonTTDayDateID', 'gibbonPlannerEntry.gibbonPlannerEntryID', 'gibbonPlannerEntry.name as lesson', 'gibbonUnit.name as unit', 'gibbonSchoolYearTerm.nameShort as termName', 'gibbonSchoolYearTerm.firstDay', 'gibbonSchoolYearTerm.lastDay', 'gibbonSchoolYearSpecialDay.name as specialDay', "CONCAT(gibbonTTDayRowClass.gibbonTTDayRowClassID, '-', gibbonTTDayDate.gibbonTTDayDateID) as identifier", 'gibbonSpace.name as spaceName', 'GROUP_CONCAT(DISTINCT teacher.gibbonPersonID) AS teacherIDs'])
            ->from('gibbonTTDayRowClass')
            ->innerJoin('gibbonTTColumnRow', 'gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID')
            ->innerJoin('gibbonTTColumn', 'gibbonTTColumnRow.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID')
            ->innerJoin('gibbonTTDayDate', 'gibbonTTDayDate.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID')
            ->innerJoin('gibbonSchoolYearTerm', 'gibbonTTDayDate.date BETWEEN gibbonSchoolYearTerm.firstDay AND gibbonSchoolYearTerm.lastDay')
            ->leftJoin('gibbonCourseClassPerson as teacher', 'gibbonTTDayRowClass.gibbonCourseClassID=teacher.gibbonCourseClassID AND (teacher.role = "Teacher" OR teacher.role="Assistant")')
            ->leftJoin('gibbonSpace', 'gibbonSpace.gibbonSpaceID=gibbonTTDayRowClass.gibbonSpaceID')
            ->leftJoin('gibbonSchoolYearSpecialDay', "gibbonSchoolYearSpecialDay.date=gibbonTTDayDate.date and gibbonSchoolYearSpecialDay.type='School Closure'")
            ->leftJoin('gibbonPlannerEntry', 'gibbonPlannerEntry.date=gibbonTTDayDate.date 
                AND gibbonPlannerEntry.timeStart=gibbonTTColumnRow.timeStart 
                AND gibbonPlannerEntry.timeEnd=gibbonTTColumnRow.timeEnd 
                AND gibbonPlannerEntry.gibbonCourseClassID=gibbonTTDayRowClass.gibbonCourseClassID')
            ->leftJoin('gibbonUnit', 'gibbonPlannerEntry.gibbonUnitID=gibbonUnit.gibbonUnitID')
            ->where('gibbonTTDayRowClass.gibbonCourseClassID=:gibbonCourseClassID')
            ->bindValue('gibbonCourseClassID', $gibbonCourseClassID)
            ->where('gibbonSchoolYearTerm.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->groupBy(['gibbonTTDayDate.date', 'gibbonTTColumnRow.name', 'gibbonPlannerEntry.gibbonPlannerEntryID']);

        return $this->runQuery($query, $criteria);
    }

    public function getPlannerTTByIDs($gibbonTTDayRowClassID, $gibbonTTDayDateID)
    {
        $data = ['gibbonTTDayRowClassID' => $gibbonTTDayRowClassID, 'gibbonTTDayDateID' => $gibbonTTDayDateID];
        $sql = "SELECT gibbonTTColumnRow.timeStart, gibbonTTColumnRow.timeEnd, gibbonTTColumnRow.name as period, gibbonTTDayDate.date
            FROM gibbonTTDayRowClass
            JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnRowID=gibbonTTDayRowClass.gibbonTTColumnRowID)
            JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID)
            WHERE gibbonTTDayRowClass.gibbonTTDayRowClassID=:gibbonTTDayRowClassID
            AND gibbonTTDayDate.gibbonTTDayDateID=:gibbonTTDayDateID";
        
        return $this->db()->selectOne($sql, $data);
    }

    public function getPlannerTTByClassTimes($gibbonCourseClassID, $date, $timeStart, $timeEnd)
    {
        $data = ['date' => $date, 'timeStart' => $timeStart, 'timeEnd' => $timeEnd, 'gibbonCourseClassID' => $gibbonCourseClassID];
        $sql = 'SELECT timeStart, timeEnd, date, gibbonTTColumnRow.name AS period, gibbonTTDayRowClassID, gibbonTTDayDateID, gibbonSpace.name as spaceName 
                FROM gibbonTTDayRowClass 
                JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID) 
                JOIN gibbonTTColumn ON (gibbonTTColumnRow.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) 
                JOIN gibbonTTDay ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) 
                JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) 
                LEFT JOIN gibbonSpace ON (gibbonSpace.gibbonSpaceID=gibbonTTDayRowClass.gibbonSpaceID)
                WHERE date=:date 
                AND timeStart=:timeStart 
                AND timeEnd=:timeEnd AND 
                gibbonCourseClassID=:gibbonCourseClassID 
                ORDER BY date, timestart';
        
        return $this->db()->selectOne($sql, $data);
    }

    public function queryHomeworkByPerson($criteria, $gibbonSchoolYearID, $gibbonPersonID)
    {
        $criteria->addFilterRules([
            'class' => function ($query, $gibbonCourseClassID) {
                return $query
                    ->where('gibbonCourseClass.gibbonCourseClassID = :gibbonCourseClassID')
                    ->bindValue('gibbonCourseClassID', $gibbonCourseClassID);
            },
            'submission' => function ($query, $homeworkSubmission) {
                return $query
                    ->where('gibbonPlannerEntry.homeworkSubmission = :homeworkSubmission')
                    ->bindValue('homeworkSubmission', $homeworkSubmission);
            },
            'viewableParents' => function ($query, $viewableParents) {
                return $query
                    ->where('gibbonPlannerEntry.viewableParents = :viewableParents')
                    ->bindValue('viewableParents', $viewableParents);
            },
            'viewableStudents' => function ($query, $viewableStudents) {
                return $query
                    ->where('gibbonPlannerEntry.viewableStudents = :viewableStudents')
                    ->bindValue('viewableStudents', $viewableStudents);
            },
            'weekly' => function ($query, $weekly) {
                return $query
                    ->where('gibbonPlannerEntry.date>:lastWeek')
                    ->bindValue('lastWeek', date('Y-m-d', strtotime('-1 week')))
                    ->where('gibbonPlannerEntry.date<=:today')
                    ->bindValue('today', date('Y-m-d'));
            },
        ]);

        $query = $this
            ->newQuery()
            ->cols([
                "'teacherRecorded' AS type",
                'gibbonPlannerEntry.gibbonPlannerEntryID',
                'gibbonPlannerEntry.gibbonUnitID',
                'gibbonPlannerEntry.gibbonCourseClassID',
                'gibbonCourse.nameShort AS course',
                'gibbonCourseClass.nameShort AS class',
                'gibbonPlannerEntry.name',
                'gibbonPlannerEntry.date',
                'gibbonPlannerEntry.timeStart',
                'gibbonPlannerEntry.timeEnd',
                'gibbonPlannerEntry.viewableStudents',
                'gibbonPlannerEntry.viewableParents',
                'gibbonPlannerEntry.homework',
                'gibbonCourseClassPerson.role',
                'gibbonPlannerEntry.homeworkDueDateTime',
                'gibbonPlannerEntry.homeworkDetails',
                'gibbonPlannerEntry.homeworkTimeCap',
                'gibbonPlannerEntry.homeworkLocation',
                'gibbonPlannerEntry.homeworkSubmission',
                'gibbonPlannerEntry.homeworkSubmissionRequired',
                'gibbonPerson.dateStart',
                'gibbonUnit.name as unit',
                ])
            ->from($this->getTableName())
            ->innerJoin('gibbonCourseClass', 'gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
            ->innerJoin('gibbonCourseClassPerson', 'gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID')
            ->innerJoin('gibbonCourse', 'gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID')
            ->leftJoin('gibbonUnit', 'gibbonUnit.gibbonUnitID=gibbonPlannerEntry.gibbonUnitID')
            ->where('gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->where('gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where("gibbonPlannerEntry.homework='Y'")
            ->where('(gibbonCourseClassPerson.dateEnrolled IS NULL OR gibbonCourseClassPerson.dateEnrolled <= gibbonPlannerEntry.date)')
            ->where("(gibbonCourseClassPerson.role NOT LIKE '%Left' OR gibbonCourseClassPerson.dateUnenrolled > gibbonPlannerEntry.homeworkDueDateTime)")
            ->where("(gibbonPlannerEntry.date < :todayDate OR (gibbonPlannerEntry.date=:todayDate AND timeEnd <= :todayTime))")
            ->bindValue('todayDate', date('Y-m-d'))
            ->bindValue('todayTime', date('H:i:s'));
          
        $this->unionAllWithCriteria($query, $criteria)
            ->cols([
                "'studentRecorded' AS type",
                'gibbonPlannerEntry.gibbonPlannerEntryID',
                'gibbonPlannerEntry.gibbonUnitID',
                'gibbonPlannerEntry.gibbonCourseClassID',
                'gibbonCourse.nameShort AS course',
                'gibbonCourseClass.nameShort AS class',
                'gibbonPlannerEntry.name',
                'gibbonPlannerEntry.date',
                'gibbonPlannerEntry.timeStart',
                'gibbonPlannerEntry.timeEnd',
                "'Y' AS viewableStudents",
                "'Y' AS viewableParents",
                "'Y' AS homework",
                'gibbonCourseClassPerson.role',
                'gibbonPlannerEntryStudentHomework.homeworkDueDateTime',
                'gibbonPlannerEntryStudentHomework.homeworkDetails',
                'gibbonPlannerEntry.homeworkTimeCap',
                'gibbonPlannerEntry.homeworkLocation',
                "'N' AS homeworkSubmission",
                "'N' AS homeworkSubmissionRequired",
                'gibbonPerson.dateStart',
                'gibbonUnit.name as unit',
                ])
            ->from($this->getTableName())
            ->innerJoin('gibbonCourseClass', 'gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
            ->innerJoin('gibbonCourseClassPerson', 'gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID')
            ->innerJoin('gibbonCourse', 'gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID')
            ->innerJoin('gibbonPlannerEntryStudentHomework', 'gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID 
            AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID')
            ->leftJoin('gibbonUnit', 'gibbonUnit.gibbonUnitID=gibbonPlannerEntry.gibbonUnitID')
            ->where('gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->where('gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('(gibbonCourseClassPerson.dateEnrolled IS NULL OR gibbonCourseClassPerson.dateEnrolled <= gibbonPlannerEntry.date)')
            ->where("(gibbonCourseClassPerson.role NOT LIKE '%Left' OR gibbonCourseClassPerson.dateUnenrolled > gibbonPlannerEntry.homeworkDueDateTime)")
            ->where("(gibbonPlannerEntry.date < :todayDate OR (gibbonPlannerEntry.date=:todayDate AND timeEnd <= :todayTime))")
            ->bindValue('todayDate', date('Y-m-d'))
            ->bindValue('todayTime', date('H:i:s'));

        return $this->runQuery($query, $criteria);
    }

    public function getPlannerEntryByID($gibbonPlannerEntryID)
    {
        $data = ['gibbonPlannerEntryID' => $gibbonPlannerEntryID];
        $sql = "SELECT * FROM gibbonPlannerEntry WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID";

        return $this->db()->selectOne($sql, $data);
    }

    public function selectPlannerEntriesByPersonAndDateRange($gibbonPersonID, $dateStart, $dateEnd)
    {
        $data = ['dateStart' => $dateStart, 'dateEnd' => $dateEnd, 'gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT CONCAT(gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.date, gibbonPlannerEntry.timeStart, gibbonPlannerEntry.timeEnd) as lessonID, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonPlannerEntry.name, gibbonPlannerEntry.date, gibbonPlannerEntry.timeStart, gibbonPlannerEntry.timeEnd, gibbonCourse.gibbonSchoolYearID, gibbonCourse.gibbonCourseID, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.name as courseName, gibbonCourse.nameShort AS courseNameShort, gibbonCourseClass.nameShort AS classNameShort, gibbonUnit.gibbonUnitID, gibbonUnit.name as unitName, gibbonTTColumnRow.name as period
        FROM gibbonCourse 
        JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) 
        JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) 
        JOIN gibbonPlannerEntry ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
        LEFT JOIN gibbonUnit ON (gibbonUnit.gibbonUnitID=gibbonPlannerEntry.gibbonUnitID)

        LEFT JOIN gibbonTTDayDate ON (gibbonTTDayDate.date=gibbonPlannerEntry.date)
        LEFT JOIN gibbonTTDay ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID)
        LEFT JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnID=gibbonTTDay.gibbonTTColumnID AND gibbonTTColumnRow.timeStart=gibbonPlannerEntry.timeStart AND gibbonTTColumnRow.timeEnd=gibbonPlannerEntry.timeEnd)
        LEFT JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonPlannerEntry.gibbonCourseClassID AND gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID AND gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID)
        LEFT JOIN gibbonTTDayRowClassException ON (gibbonTTDayRowClassException.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID AND gibbonTTDayRowClassException.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID)

        WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID
        AND gibbonPlannerEntry.date BETWEEN :dateStart AND :dateEnd
        AND gibbonCourseClassPerson.role NOT LIKE '%- Left'
        GROUP BY gibbonPlannerEntry.gibbonPlannerEntryID
        HAVING COUNT(gibbonTTDayRowClassException.gibbonTTDayRowClassExceptionID) = 0
        ORDER BY timeStart, timeEnd, FIND_IN_SET(gibbonCourseClassPerson.role, 'Teacher,Assistant,Student') DESC";

        return $this->db()->select($sql, $data);
    }

    public function getPlannerEntryByClassTimes($gibbonCourseClassID, $date, $timeStart, $timeEnd)
    {
        $data = ['date' => $date, 'timeStart' => $timeStart, 'timeEnd' => $timeEnd, 'gibbonCourseClassID' => $gibbonCourseClassID];
        $sql = "SELECT * FROM gibbonPlannerEntry WHERE gibbonCourseClassID=:gibbonCourseClassID AND date=:date AND timeStart=:timeStart AND timeEnd=:timeEnd GROUP BY name";

        return $this->db()->selectOne($sql, $data);
    }

    public function selectPlannerEntriesByUnitAndClass($gibbonUnitID, $gibbonCourseClassID)
    {
        $data = ['gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonUnitID' => $gibbonUnitID];
        $sql = "SELECT * FROM gibbonPlannerEntry WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonUnitID=:gibbonUnitID ORDER BY date, timeStart";

        return $this->db()->select($sql, $data);
    }

    public function selectUpcomingHomeworkByStudent($gibbonSchoolYearID, $gibbonPersonID, $viewableBy = 'viewableStudents')
    {
        $data = [
            'gibbonSchoolYearID' => $gibbonSchoolYearID,
            'gibbonPersonID' => $gibbonPersonID,
            'todayTime' => date('Y-m-d H:i:s'),
            'todayDate' => date('Y-m-d'),
            'time' => date('H:i:s'),
        ];
        // UNION Teacher Online + Teacher Manual + Student Manual
        $sql = "
            (SELECT 'teacherRecorded' AS type, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkDueDateTime, role, (CASE WHEN gibbonPlannerEntryHomework.version='Final' THEN 'Y' ELSE 'N' END) AS homeworkComplete, (CASE WHEN gibbonPlannerEntryHomework.gibbonPlannerEntryHomeworkID IS NOT NULL THEN 'Y' ELSE 'N' END) as onlineSubmission
                FROM gibbonPlannerEntry 
                JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) 
                JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) 
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) 
                LEFT JOIN gibbonPlannerEntryHomework ON (gibbonPlannerEntryHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID 
                    AND gibbonPlannerEntryHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID)
                WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID 
                AND homework='Y' 
                AND (role='Teacher' OR (role='Student' AND $viewableBy='Y')) 
                AND homeworkDueDateTime>:todayTime 
                AND gibbonPlannerEntry.homeworkSubmission='Y'
                AND ((date<:todayDate) OR (date=:todayDate AND timeEnd<=:time))
                AND (gibbonCourseClassPerson.dateEnrolled IS NULL OR gibbonCourseClassPerson.dateEnrolled <= gibbonPlannerEntry.date)
                AND (gibbonCourseClassPerson.dateUnenrolled IS NULL OR gibbonCourseClassPerson.dateUnenrolled > gibbonPlannerEntry.homeworkDueDateTime)
            )
            UNION
            (SELECT 'teacherRecorded' AS type, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkDueDateTime, role, gibbonPlannerEntryStudentTracker.homeworkComplete, 'N' as onlineSubmission
                FROM gibbonPlannerEntry 
                JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) 
                JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) 
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) 
                LEFT JOIN gibbonPlannerEntryStudentTracker ON (gibbonPlannerEntryStudentTracker.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID 
                    AND gibbonPlannerEntryStudentTracker.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID)

                WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID 
                AND homework='Y' 
                AND (role='Teacher' OR (role='Student' AND $viewableBy='Y')) 
                AND homeworkDueDateTime>:todayTime 
                AND gibbonPlannerEntry.homeworkSubmission<>'Y'
                AND ((date<:todayDate) OR (date=:todayDate AND timeEnd<=:time))
                AND (gibbonCourseClassPerson.dateEnrolled IS NULL OR gibbonCourseClassPerson.dateEnrolled <= gibbonPlannerEntry.date)
                AND (gibbonCourseClassPerson.dateUnenrolled IS NULL OR gibbonCourseClassPerson.dateUnenrolled > gibbonPlannerEntry.homeworkDueDateTime)
            )
            UNION
            (SELECT 'studentRecorded' AS type, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, 'Y' AS viewableStudents, 'Y' AS viewableParents, 'Y' AS homework, gibbonPlannerEntryStudentHomework.homeworkDueDateTime, gibbonCourseClassPerson.role, gibbonPlannerEntryStudentHomework.homeworkComplete, 'N' as onlineSubmission FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) 
                JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) 
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) 
                JOIN gibbonPlannerEntryStudentHomework ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID 
                AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) 
                LEFT JOIN gibbonPlannerEntryHomework ON (gibbonPlannerEntryHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID 
                    AND gibbonPlannerEntryHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID AND gibbonPlannerEntryHomework.version='Final')
                WHERE gibbonSchoolYearID=:gibbonSchoolYearID 
                AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID 
                AND (gibbonCourseClassPerson.role='Teacher' OR (gibbonCourseClassPerson.role='Student' AND $viewableBy='Y')) 
                AND gibbonPlannerEntryStudentHomework.homeworkDueDateTime>:todayTime 
                AND ((date<:todayDate) OR (date=:todayDate AND timeEnd<=:time))
                AND (gibbonCourseClassPerson.dateEnrolled IS NULL OR gibbonCourseClassPerson.dateEnrolled <= gibbonPlannerEntry.date)
                AND (gibbonCourseClassPerson.dateUnenrolled IS NULL OR gibbonCourseClassPerson.dateUnenrolled > gibbonPlannerEntry.homeworkDueDateTime)
            )
            ORDER BY homeworkDueDateTime, type";

        return $this->db()->select($sql, $data);
    }

    public function selectTeacherRecordedHomeworkTrackerByStudent($gibbonSchoolYearID, $gibbonPersonID)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = "
            SELECT TRIM(LEADING '0' FROM gibbonPlannerEntryStudentTracker.gibbonPlannerEntryID) as groupBy, 'teacherRecorded' AS type, homeworkComplete 
            FROM gibbonPlannerEntryStudentTracker 
            JOIN gibbonPlannerEntry ON (gibbonPlannerEntryStudentTracker.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) 
            JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) 
            JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) 
            WHERE gibbonSchoolYearID=:gibbonSchoolYearID 
            AND gibbonPersonID=:gibbonPersonID 
            AND homeworkComplete='Y'
            ORDER BY groupBy, type
            ";

        return $this->db()->select($sql, $data);
    }

    public function selectStudentRecordedHomeworkTrackerByStudent($gibbonSchoolYearID, $gibbonPersonID)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = "
            SELECT TRIM(LEADING '0' FROM gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID) as groupBy,  'studentRecorded' AS type, homeworkComplete
            FROM gibbonPlannerEntryStudentHomework 
            JOIN gibbonPlannerEntry ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) 
            JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) 
            JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) 
            WHERE gibbonSchoolYearID=:gibbonSchoolYearID 
            AND gibbonPersonID=:gibbonPersonID 
            AND homeworkComplete='Y'
            ORDER BY groupBy, type
            ";

        return $this->db()->select($sql, $data);
    }

    public function selectHomeworkSubmissionsByStudent($gibbonSchoolYearID, $gibbonPersonID)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT TRIM(LEADING '0' FROM gibbonPlannerEntryHomework.gibbonPlannerEntryID) as groupBy, gibbonPlannerEntryHomework.* 
            FROM gibbonPlannerEntryHomework 
            JOIN gibbonPlannerEntry ON (gibbonPlannerEntry.gibbonPlannerEntryID=gibbonPlannerEntryHomework.gibbonPlannerEntryID) 
            JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) 
            JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) 
            WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID
            AND gibbonPlannerEntryHomework.gibbonPersonID=:gibbonPersonID 
            ORDER BY count DESC";

        return $this->db()->select($sql, $data);
    }

    public function selectHomeworkSubmissionCounts($gibbonPlannerEntryID)
    {
        $gibbonPlannerEntryIDList = is_array($gibbonPlannerEntryID)? $gibbonPlannerEntryID : [$gibbonPlannerEntryID];
        $gibbonPlannerEntryIDList = array_map(function($item) {
            return str_pad($item, 14, '0', STR_PAD_LEFT);
        }, $gibbonPlannerEntryIDList);

        $data = ['gibbonPlannerEntryIDList' => implode(',', $gibbonPlannerEntryIDList)];
        $sql = "SELECT TRIM(LEADING '0' FROM gibbonPlannerEntry.gibbonPlannerEntryID) as groupBy,
            COUNT(DISTINCT CASE WHEN gibbonPlannerEntryHomework.version='Final' AND gibbonPlannerEntryHomework.status='On Time' THEN  gibbonPlannerEntryHomework.gibbonPersonID END) as onTime,
            COUNT(DISTINCT CASE WHEN gibbonPlannerEntryHomework.version='Final' AND gibbonPlannerEntryHomework.status='Late' THEN  gibbonPlannerEntryHomework.gibbonPersonID END) as late,
            (SELECT COUNT(*) FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonCourseClassPerson.gibbonCourseClassID=gibbonPlannerEntry.gibbonCourseClassID AND role='Student' AND gibbonPerson.status='Full' AND (gibbonCourseClassPerson.dateEnrolled IS NULL OR gibbonCourseClassPerson.dateEnrolled <= CURRENT_DATE) AND (gibbonCourseClassPerson.dateUnenrolled IS NULL OR gibbonCourseClassPerson.dateUnenrolled > CURRENT_DATE) ) as total
            FROM gibbonPlannerEntry
            LEFT JOIN gibbonPlannerEntryHomework ON (gibbonPlannerEntry.gibbonPlannerEntryID=gibbonPlannerEntryHomework.gibbonPlannerEntryID)
            WHERE FIND_IN_SET(gibbonPlannerEntry.gibbonPlannerEntryID, :gibbonPlannerEntryIDList)
            GROUP BY gibbonPlannerEntry.gibbonPlannerEntryID
            ";

        return $this->db()->select($sql, $data);


    }

    public function selectAllUpcomingHomework($gibbonSchoolYearID)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'homeworkDueDateTime' => date('Y-m-d H:i:s'), 'date1' => date('Y-m-d'), 'date2' => date('Y-m-d'), 'timeEnd' => date('H:i:s')];
        $sql = "SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkDueDateTime 
            FROM gibbonPlannerEntry 
            JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) 
            JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) 
            WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND homework='Y' AND homeworkDueDateTime>:homeworkDueDateTime AND ((date<:date1) OR (date=:date2 AND timeEnd<=:timeEnd)) ORDER BY homeworkDueDateTime";

        return $this->db()->select($sql, $data);
    }

    public function selectPlannerClassesByPerson($gibbonSchoolYearID, $gibbonPersonID)
    {
        $data = [
            'gibbonSchoolYearID' => $gibbonSchoolYearID,
            'gibbonPersonID' => $gibbonPersonID,
            'today' => date('Y-m-d'),
        ];
        $sql = "SELECT DISTINCT gibbonCourseClass.gibbonCourseClassID as value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) as name 
            FROM gibbonPlannerEntry 
            JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) 
            JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) 
            JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) 
            WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID 
            AND gibbonSchoolYearID=:gibbonSchoolYearID  
            AND NOT role='Student - Left' AND NOT role='Teacher - Left' 
            AND homework='Y' AND date<=:today 
            AND (gibbonCourseClassPerson.dateEnrolled IS NULL OR gibbonCourseClassPerson.dateEnrolled <= gibbonPlannerEntry.date)
            AND (gibbonCourseClassPerson.dateUnenrolled IS NULL OR gibbonCourseClassPerson.dateUnenrolled > gibbonPlannerEntry.date)
            ORDER BY name";

        return $this->db()->select($sql, $data);
    }

    public function selectPlannerGuests($gibbonPlannerEntryID)
    {
        $data = ['gibbonPlannerEntryID' => $gibbonPlannerEntryID];
        $sql = "SELECT title, surname, preferredName, image_240, gibbonPlannerEntryGuest.role
                FROM gibbonPlannerEntryGuest 
                JOIN gibbonPerson ON gibbonPlannerEntryGuest.gibbonPersonID=gibbonPerson.gibbonPersonID 
                JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) 
                WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID 
                AND status='Full' 
                ORDER BY role DESC, surname, preferredName";

        return $this->db()->select($sql, $data);
    }

    public function getPlannerClassDetails($gibbonCourseClassID)
    {
        $data = ['gibbonCourseClassID' => $gibbonCourseClassID];
        $sql = "SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class 
                FROM gibbonCourseClass
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) 
                WHERE gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID";

        return $this->db()->selectOne($sql, $data);
    }

    public function getLatestLessonByClass($gibbonCourseClassID)
    {
        $data = ['gibbonCourseClassID' => $gibbonCourseClassID];
        $sql = "SELECT * FROM gibbonPlannerEntry 
                WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID
                ORDER BY date DESC 
                LIMIT 1";

        return $this->db()->selectOne($sql, $data);
    }

    public function selectUpcomingPlannerTTByDate($gibbonCourseClassID, $date)
    {
        $data = ['gibbonCourseClassID' => $gibbonCourseClassID, 'date' => $date];
        $sql = "SELECT gibbonTTColumnRow.timeStart, gibbonTTColumnRow.timeEnd, gibbonTTDayDate.date
                FROM gibbonTTDayRowClass
                JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID)
                JOIN gibbonTTColumn ON (gibbonTTColumnRow.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID)
                JOIN gibbonTTDay ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID)
                JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID)
                LEFT JOIN gibbonSchoolYearSpecialDay ON (gibbonSchoolYearSpecialDay.date=gibbonTTDayDate.date 
                    AND gibbonSchoolYearSpecialDay.type='School Closure')
                WHERE gibbonTTDayRowClass.gibbonCourseClassID=:gibbonCourseClassID
                AND gibbonTTDayDate.date>=:date
                AND gibbonSchoolYearSpecialDayID IS NULL
                ORDER BY gibbonTTDayDate.date, gibbonTTColumnRow.timestart
                LIMIT 0, 10";

        return $this->db()->select($sql, $data);
    }

    public function getPreviousLesson($gibbonCourseClassID, $date, $timeStart, $role)
    {
        $data = ['gibbonCourseClassID' => $gibbonCourseClassID, 'date' => $date, 'timeStart' => $timeStart];
        $sql = "SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents 
            FROM gibbonPlannerEntry
            JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
            JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
            WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID 
            AND (date<:date OR (date=:date AND timeStart<:timeStart)) ";

        if ($role == 'Student') {
            $sql .= ' AND viewableStudents="Y" ';
        } elseif ($role == 'Parent') {
            $sql .= ' AND viewableParents="Y" ';
        }
        $sql .= " ORDER BY date DESC, timeStart DESC LIMIT 1";

        return $this->db()->selectOne($sql, $data);
    }

    public function getNextLesson($gibbonCourseClassID, $date, $timeStart, $role)
    {
        $data = ['gibbonCourseClassID' => $gibbonCourseClassID, 'date' => $date, 'timeStart' => $timeStart];
        $sql = "SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents 
            FROM gibbonPlannerEntry
            JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
            JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
            WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID 
            AND (date>:date OR (date=:date AND timeStart>:timeStart)) ";

        if ($role == 'Student') {
            $sql .= ' AND viewableStudents="Y" ';
        } elseif ($role == 'Parent') {
            $sql .= ' AND viewableParents="Y" ';
        }
        $sql .= " ORDER BY date, timeStart LIMIT 1";

        return $this->db()->selectOne($sql, $data);
    }
}
