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
class ExternalAssessmentStudentEntryGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonExternalAssessmentStudentEntry';
    private static $primaryKey = 'gibbonExternalAssessmentStudentEntryID';

    private static $searchableColumns = [];

    public function selectGCSEGradesByStudentID($gibbonExternalAssessmentStudentID)
    {
        $data = ['category' => '%GCSE Target Grades', 'gibbonExternalAssessmentStudentID' => $gibbonExternalAssessmentStudentID];
        $sql = 'SELECT * FROM gibbonExternalAssessmentStudentEntry JOIN gibbonExternalAssessmentField ON (gibbonExternalAssessmentStudentEntry.gibbonExternalAssessmentFieldID=gibbonExternalAssessmentField.gibbonExternalAssessmentFieldID) WHERE category LIKE :category AND gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID AND NOT (gibbonScaleGradeID IS NULL) ORDER BY name';

        return $this->db()->select($sql, $data);
    }

    public function selectTargetGradesByExternalAssessmentStudentID($gibbonExternalAssessmentStudentID)
    {
        $data = ['gibbonExternalAssessmentStudentID' => $gibbonExternalAssessmentStudentID];
        $sql = "SELECT * FROM gibbonExternalAssessmentStudentEntry JOIN gibbonExternalAssessmentField ON (gibbonExternalAssessmentStudentEntry.gibbonExternalAssessmentFieldID=gibbonExternalAssessmentField.gibbonExternalAssessmentFieldID) JOIN gibbonScaleGrade ON (gibbonExternalAssessmentStudentEntry.gibbonScaleGradeID=gibbonScaleGrade.gibbonScaleGradeID) WHERE category LIKE '%Target Grade' AND gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID AND NOT (gibbonExternalAssessmentStudentEntry.gibbonScaleGradeID IS NULL) ORDER BY name";

        return $this->db()->select($sql, $data);
    }

    public function selectFinalGradesByExternalAssessmentStudentID($gibbonExternalAssessmentStudentID)
    {
        $data = ['gibbonExternalAssessmentStudentID' => $gibbonExternalAssessmentStudentID];
        $sql = "SELECT * FROM gibbonExternalAssessmentStudentEntry JOIN gibbonExternalAssessmentField ON (gibbonExternalAssessmentStudentEntry.gibbonExternalAssessmentFieldID=gibbonExternalAssessmentField.gibbonExternalAssessmentFieldID) JOIN gibbonScaleGrade ON (gibbonExternalAssessmentStudentEntry.gibbonScaleGradeID=gibbonScaleGrade.gibbonScaleGradeID) WHERE category LIKE '%Final Grade' AND gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID AND NOT (gibbonExternalAssessmentStudentEntry.gibbonScaleGradeID IS NULL) ORDER BY name";

        return $this->db()->select($sql, $data);
    }
}
