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

namespace Gibbon\Domain\Markbook;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * Markbook Entry Gateway
 *
 * @version v20
 * @since   v20
 */
class MarkbookEntryGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonMarkbookEntry';
    private static $primaryKey = 'gibbonMarkbookEntryID';
    private static $searchableColumns = [];
    
    public function selectMarkbookEntriesByClassAndStudent($gibbonCourseClassID, $gibbonPersonIDStudent)
    {
        $data = ['gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent];
        $sql = "SELECT gibbonMarkbookColumn.type, gibbonMarkbookEntry.*, gibbonMarkbookColumn.*, gibbonMarkbookEntry.comment as commentValue, gibbonMarkbookWeight.calculate
                FROM gibbonMarkbookEntry 
                JOIN gibbonMarkbookColumn ON (gibbonMarkbookColumn.gibbonMarkbookColumnID=gibbonMarkbookEntry.gibbonMarkbookColumnID) 
                JOIN gibbonScale ON (gibbonMarkbookColumn.gibbonScaleIDAttainment=gibbonScale.gibbonScaleID)
                LEFT JOIN gibbonMarkbookWeight ON (gibbonMarkbookWeight.type=gibbonMarkbookColumn.type AND gibbonMarkbookWeight.gibbonCourseClassID=gibbonMarkbookColumn.gibbonCourseClassID)
                WHERE gibbonMarkbookColumn.gibbonCourseClassID=:gibbonCourseClassID
                AND gibbonMarkbookColumn.attainment='Y'
                AND gibbonMarkbookColumn.attainmentWeighting > 0.0
                AND gibbonMarkbookColumn.attainmentType = 'Summative'
                AND gibbonMarkbookEntry.gibbonPersonIDStudent=:gibbonPersonIDStudent
                AND gibbonMarkbookEntry.attainmentValue IS NOT NULL
                AND gibbonMarkbookEntry.attainmentValue <> ''
                AND gibbonScale.gibbonScaleID = 0001
                ORDER BY gibbonMarkbookWeight.calculate, gibbonMarkbookColumn.type, gibbonMarkbookColumn.date
                ";

        return $this->db()->select($sql, $data);
    }
}
