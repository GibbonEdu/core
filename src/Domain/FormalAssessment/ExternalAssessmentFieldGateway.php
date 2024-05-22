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
class ExternalAssessmentFieldGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonExternalAssessmentField';
    private static $primaryKey = 'gibbonExternalAssessmentFieldID';

    private static $searchableColumns = [];

    public function selectFieldsByExternalAssessment($gibbonExternalAssessmentID)
    {
        $data = ['gibbonExternalAssessmentID' => $gibbonExternalAssessmentID];
        $sql = 'SELECT category, gibbonExternalAssessmentField.*, gibbonScale.usage FROM gibbonExternalAssessmentField JOIN gibbonScale ON (gibbonExternalAssessmentField.gibbonScaleID=gibbonScale.gibbonScaleID) WHERE gibbonExternalAssessmentID=:gibbonExternalAssessmentID ORDER BY category, gibbonExternalAssessmentField.order';

        return $this->db()->select($sql, $data);
    }

    public function selectFieldsByExternalAssessmentAndStudent($gibbonExternalAssessmentID, $gibbonExternalAssessmentStudentID)
    {
        $data= ['gibbonExternalAssessmentID' => $gibbonExternalAssessmentID, 'gibbonExternalAssessmentStudentID' => $gibbonExternalAssessmentStudentID];
        $sql = 'SELECT category, gibbonExternalAssessmentStudentEntryID, gibbonExternalAssessmentField.*, gibbonScale.usage, gibbonExternalAssessmentStudentEntry.gibbonScaleGradeID FROM gibbonExternalAssessmentField JOIN gibbonScale ON (gibbonExternalAssessmentField.gibbonScaleID=gibbonScale.gibbonScaleID) LEFT JOIN gibbonExternalAssessmentStudentEntry ON (gibbonExternalAssessmentField.gibbonExternalAssessmentFieldID=gibbonExternalAssessmentStudentEntry.gibbonExternalAssessmentFieldID) WHERE gibbonExternalAssessmentID=:gibbonExternalAssessmentID AND gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID ORDER BY category, gibbonExternalAssessmentField.order';

        return $this->db()->select($sql, $data);
    }
}
