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
 * @version v21
 * @since   v21
 */
class ExternalAssessmentStudentGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonExternalAssessmentStudent';
    private static $primaryKey = 'gibbonExternalAssessmentStudentID';

    private static $searchableColumns = [];

    public function selectGCSEGradesByPersonID($gibbonPersonID)
    {
        $data = ['gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT * FROM gibbonExternalAssessment JOIN gibbonExternalAssessmentStudent ON (gibbonExternalAssessmentStudent.gibbonExternalAssessmentID=gibbonExternalAssessment.gibbonExternalAssessmentID) WHERE name='GCSE/iGCSE' AND gibbonPersonID=:gibbonPersonID ORDER BY date DESC";

        return $this->db()->select($sql, $data);
    }

    public function getStudentExternalAssessmentDetails($gibbonExternalAssessmentStudentID)
    {
        $data = ['gibbonExternalAssessmentStudentID' => $gibbonExternalAssessmentStudentID];
        $sql = 'SELECT gibbonExternalAssessmentStudent.*, gibbonExternalAssessment.name AS assessment, gibbonExternalAssessment.allowFileUpload FROM gibbonExternalAssessmentStudent JOIN gibbonExternalAssessment ON (gibbonExternalAssessmentStudent.gibbonExternalAssessmentID=gibbonExternalAssessment.gibbonExternalAssessmentID) WHERE gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID';

        return $this->db()->selectOne($sql, $data);
    }

    public function selectStudentExternalAssessmentGrades($gibbonPersonID, $gibbonExternalAssessmentFieldID)
    {
        $data = ['gibbonPersonID' => $gibbonPersonID, 'gibbonExternalAssessmentFieldID' => $gibbonExternalAssessmentFieldID];
        $sql = "SELECT gibbonScaleGrade.value, gibbonScaleGrade.descriptor, gibbonExternalAssessmentStudent.date FROM gibbonExternalAssessmentStudentEntry JOIN gibbonExternalAssessmentStudent ON (gibbonExternalAssessmentStudentEntry.gibbonExternalAssessmentStudentID=gibbonExternalAssessmentStudent.gibbonExternalAssessmentStudentID) JOIN gibbonScaleGrade ON (gibbonExternalAssessmentStudentEntry.gibbonScaleGradeID=gibbonScaleGrade.gibbonScaleGradeID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonExternalAssessmentFieldID=:gibbonExternalAssessmentFieldID AND NOT gibbonExternalAssessmentStudentEntry.gibbonScaleGradeID='' ORDER BY date DESC";

        return $this->db()->select($sql, $data);
    }
}
