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

namespace Gibbon\Domain\FormalAssessment;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * @version v28
 * @since   v28
 */
class InternalAssessmentColumnGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonInternalAssessmentColumn';
    private static $primaryKey = 'gibbonInternalAssessmentColumnID';

    private static $searchableColumns = [];
 

    public function selectColumnsByClass($gibbonCourseClassID)
    {
        $data = ['gibbonCourseClassID' => $gibbonCourseClassID];
        $sql = 'SELECT * FROM gibbonInternalAssessmentColumn WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY complete, completeDate DESC, name';

        return $this->db()->select($sql, $data);
    }

    public function selectLimitedColumns($gibbonCourseClassID, $limit, $columnsPerPage)
    {
        $data = ['gibbonCourseClassID' => $gibbonCourseClassID];
        $sql = 'SELECT * FROM gibbonInternalAssessmentColumn WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY complete, completeDate DESC LIMIT '.$limit.', '.$columnsPerPage;

        return $this->db()->select($sql, $data);
    }

    public function getScaleByInternalAssessmentColumn($gibbonInternalAssessmentColumnID)
    {
        $data = ['gibbonInternalAssessmentColumnID' => $gibbonInternalAssessmentColumnID];
        $sql = "SELECT gibbonInternalAssessmentColumn.*, attainmentScale.name as scaleNameAttainment, attainmentScale.usage as usageAttainment, attainmentScale.lowestAcceptable as lowestAcceptableAttainment, effortScale.name as scaleNameEffort, effortScale.usage as usageEffort, effortScale.lowestAcceptable as lowestAcceptableEffort FROM gibbonInternalAssessmentColumn LEFT JOIN gibbonScale as attainmentScale ON (attainmentScale.gibbonScaleID=gibbonInternalAssessmentColumn.gibbonScaleIDAttainment) LEFT JOIN gibbonScale as effortScale ON (effortScale.gibbonScaleID=gibbonInternalAssessmentColumn.gibbonScaleIDEffort) WHERE gibbonInternalAssessmentColumnID=:gibbonInternalAssessmentColumnID";

        return $this->db()->selectOne($sql, $data);
    }

    public function selectStudentsByCourseClassAndInternalAssessmentColumn($gibbonCourseClassID, $gibbonInternalAssessmentColumnID)
    {
        $data = ['gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonInternalAssessmentColumnID' => $gibbonInternalAssessmentColumnID, 'today' => date('Y-m-d')];
        $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonPerson.title, gibbonPerson.surname, gibbonPerson.preferredName, gibbonPerson.dateStart, gibbonInternalAssessmentEntry.*
            FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonInternalAssessmentEntry ON (gibbonInternalAssessmentEntry.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID AND gibbonInternalAssessmentEntry.gibbonInternalAssessmentColumnID=:gibbonInternalAssessmentColumnID) WHERE gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourseClassPerson.reportable='Y' AND gibbonCourseClassPerson.role='Student' AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL  OR dateEnd>=:today) ORDER BY gibbonPerson.surname, gibbonPerson.preferredName";

        return $this->db()->select($sql, $data);
    }

    public function selectInternalAssessmentEntry($gibbonInternalAssessmentColumnID, $gibbonPersonIDStudent)
    {
        $data = ['gibbonInternalAssessmentColumnID' => $gibbonInternalAssessmentColumnID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent];
        $sql = 'SELECT * FROM gibbonInternalAssessmentEntry WHERE gibbonInternalAssessmentColumnID=:gibbonInternalAssessmentColumnID AND gibbonPersonIDStudent=:gibbonPersonIDStudent';

        return $this->db()->select($sql, $data);
    }

    public function getInternalAssessmentEntryByStudent($gibbonInternalAssessmentColumnID, $gibbonPersonIDStudent)
    {
        $data = ['gibbonInternalAssessmentColumnID' => $gibbonInternalAssessmentColumnID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent];
        $sql = 'SELECT * FROM gibbonInternalAssessmentEntry WHERE gibbonInternalAssessmentColumnID=:gibbonInternalAssessmentColumnID AND gibbonPersonIDStudent=:gibbonPersonIDStudent';

        return $this->db()->selectOne($sql, $data);
    }
}
