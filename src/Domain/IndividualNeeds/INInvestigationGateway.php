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

namespace Gibbon\Domain\IndividualNeeds;

use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\ScrubbableGateway;
use Gibbon\Domain\Traits\Scrubbable;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\Traits\ScrubByPerson;

/**
 * Investigations Gateway
 *
 * @version v19
 * @since   v19
 */
class INInvestigationGateway extends QueryableGateway implements ScrubbableGateway
{
    use TableAware;
    use Scrubbable;
    use ScrubByPerson;

    private static $tableName = 'gibbonINInvestigation';
    private static $primaryKey = 'gibbonINInvestigationID';

    private static $searchableColumns = [];

    private static $scrubbableKey = 'gibbonPersonIDStudent';
    private static $scrubbableColumns = ['date' => '','reason' => '','strategiesTried' => '','parentsInformed' => '','parentsResponse'=> null,'resolutionDetails'=> null];

    /**
     * @param QueryCriteria $criteria
     * @param int $gibbonSchoolYearID
     * @param int $gibbonPersonIDCreator
     * @return DataSet
     */
    public function queryInvestigations(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonPersonIDCreator = null)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonINInvestigation.*',
                'student.gibbonPersonID',
                'student.surname',
                'student.preferredName',
                'gibbonFormGroup.nameShort AS formGroup',
                'creator.title AS titleCreator',
                'creator.surname AS surnameCreator',
                'creator.preferredName AS preferredNameCreator'
            ])
            ->innerJoin('gibbonPerson AS student', 'gibbonINInvestigation.gibbonPersonIDStudent=student.gibbonPersonID')
            ->innerJoin('gibbonStudentEnrolment', 'student.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonFormGroup', 'gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID')
            ->leftJoin('gibbonPerson AS creator', 'gibbonINInvestigation.gibbonPersonIDCreator=creator.gibbonPersonID')
            ->where('gibbonINInvestigation.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonStudentEnrolment.gibbonSchoolYearID=gibbonINInvestigation.gibbonSchoolYearID');

        if (!empty($gibbonPersonIDCreator)) {
            $query->where('gibbonINInvestigation.gibbonPersonIDCreator=:gibbonPersonIDCreator OR gibbonFormGroup.gibbonPersonIDTutor=:gibbonPersonIDCreator OR gibbonFormGroup.gibbonPersonIDTutor2=:gibbonPersonIDCreator OR gibbonFormGroup.gibbonPersonIDTutor3=:gibbonPersonIDCreator')
                ->bindValue('gibbonPersonIDCreator', $gibbonPersonIDCreator);
        }

        $criteria->addFilterRules([
            'student' => function ($query, $gibbonPersonID) {
                return $query
                    ->where('gibbonINInvestigation.gibbonPersonIDStudent=:gibbonPersonID')
                    ->bindValue('gibbonPersonID', $gibbonPersonID);
            },
            'formGroup' => function ($query, $gibbonFormGroupID) {
                return $query
                    ->where('gibbonStudentEnrolment.gibbonFormGroupID=:gibbonFormGroupID')
                    ->bindValue('gibbonFormGroupID', $gibbonFormGroupID);
            },
            'yearGroup' => function ($query, $gibbonYearGroupID) {
                return $query
                    ->where('gibbonStudentEnrolment.gibbonYearGroupID=:gibbonYearGroupID')
                    ->bindValue('gibbonYearGroupID', $gibbonYearGroupID);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    /**
     * @param int $gibbonINInvestigationID
     * @return array
     */
    public function getInvestigationByID($gibbonINInvestigationID)
    {
        $query = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols([
                'gibbonINInvestigation.*',
                'student.gibbonPersonID',
                'student.surname',
                'student.preferredName',
                'gibbonFormGroup.nameShort AS formGroup',
                'creator.title AS titleCreator',
                'creator.surname AS surnameCreator',
                'creator.preferredName AS preferredNameCreator',
                'gibbonFormGroup.gibbonPersonIDTutor',
                'gibbonFormGroup.gibbonPersonIDTutor2',
                'gibbonFormGroup.gibbonPersonIDTutor3',
                'gibbonYearGroup.gibbonPersonIDHOY',
                'gibbonYearGroup.gibbonYearGroupID'
            ])
            ->innerJoin('gibbonPerson AS student', 'gibbonINInvestigation.gibbonPersonIDStudent=student.gibbonPersonID')
            ->innerJoin('gibbonStudentEnrolment', 'student.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonFormGroup', 'gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID')
            ->innerJoin('gibbonYearGroup', 'gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID')
            ->leftJoin('gibbonPerson AS creator', 'gibbonINInvestigation.gibbonPersonIDCreator=creator.gibbonPersonID')
            ->where('gibbonStudentEnrolment.gibbonSchoolYearID=gibbonINInvestigation.gibbonSchoolYearID')
            ->bindValue('gibbonINInvestigationID', $gibbonINInvestigationID)
            ->where('gibbonINInvestigation.gibbonINInvestigationID=:gibbonINInvestigationID');

        return $this->runSelect($query)->fetch();
    }

    /**
     * @param int $gibbonSchoolYearID
     * @param int $gibbonPersonID
     * @return result
     */
    public function queryTeachersByInvestigation($gibbonSchoolYearID, $gibbonPersonID)
    {
        $result = null;

        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID);
        $sql = "SELECT gibbonCourseClassTeacher.gibbonCourseClassPersonID, gibbonCourseClassTeacher.gibbonPersonID, gibbonCourseClass.gibbonCourseClassID, gibbonCourseClass.nameShort AS class, gibbonCourse.nameShort AS course, surname, preferredName
            FROM gibbonCourse
                JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                JOIN gibbonCourseClassPerson AS gibbonCourseClassStudent ON (gibbonCourseClassStudent.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND gibbonCourseClassStudent.role='Student')
                JOIN gibbonCourseClassPerson AS gibbonCourseClassTeacher ON (gibbonCourseClassTeacher.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND gibbonCourseClassTeacher.role='Teacher')
                JOIN gibbonPerson ON (gibbonCourseClassTeacher.gibbonPersonID=gibbonPerson.gibbonPersonID)
            WHERE gibbonSchoolYearID=:gibbonSchoolYearID
                AND gibbonCourseClassStudent.gibbonPersonID=:gibbonPersonID
                AND gibbonCourseClass.reportable='Y'
                AND gibbonCourseClassStudent.reportable='Y'
            ORDER BY course, class";
        $result = $this->db()->select($sql, $data);

        return $result;
    }

    /**
     * @param int $gibbonSchoolYearID
     * @param int $gibbonPersonID
     * @return result
     */
    public function queryHOYByInvestigation($gibbonSchoolYearID, $gibbonPersonID)
    {
        $result = null;

        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID);
        $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName
            FROM gibbonStudentEnrolment
                JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
                JOIN gibbonPerson ON (gibbonYearGroup.gibbonPersonIDHOY=gibbonPerson.gibbonPersonID)
            WHERE gibbonSchoolYearID=:gibbonSchoolYearID
                AND gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonID";
        $result = $this->db()->select($sql, $data);

        return $result;
    }
}
