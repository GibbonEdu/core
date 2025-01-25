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

namespace Gibbon\Domain\Rubrics;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * @version v17
 * @since   v17
 */
class RubricGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonRubric';
    private static $primaryKey = 'gibbonRubricID';
    private static $searchableColumns = ['gibbonRubric.name', 'gibbonRubric.category'];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryRubrics(QueryCriteria $criteria, $active = null, $gibbonYearGroupID = null)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonRubricID', 'gibbonRubric.scope', 'gibbonRubric.category', 'gibbonRubric.name', 'gibbonRubric.description', 'gibbonRubric.active', 'gibbonRubric.gibbonDepartmentID', 'gibbonDepartment.name AS learningArea', 
                "GROUP_CONCAT(DISTINCT gibbonYearGroup.nameShort ORDER BY gibbonYearGroup.sequenceNumber SEPARATOR ', ') as yearGroups",
                "COUNT(DISTINCT gibbonYearGroup.gibbonYearGroupID) as yearGroupCount",
            ])
            ->leftJoin('gibbonDepartment', "gibbonRubric.scope = 'Learning Area' AND gibbonDepartment.gibbonDepartmentID=gibbonRubric.gibbonDepartmentID")
            ->leftJoin('gibbonYearGroup', 'FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonRubric.gibbonYearGroupIDList)')
            ->groupBy(['gibbonRubric.gibbonRubricID']);
            
        if (!empty($active)) {
            $query->where('gibbonRubric.active = :active')
                ->bindValue('active', $active);
        }

        if (!empty($gibbonYearGroupID)) {
            $query->where('FIND_IN_SET(:gibbonYearGroupID, gibbonRubric.gibbonYearGroupIDList)')
                ->bindValue('gibbonYearGroupID', $gibbonYearGroupID);
        }

        $criteria->addFilterRules([
            'department' => function ($query, $gibbonDepartmentID) {
                return $query
                    ->where('gibbonRubric.gibbonDepartmentID = :gibbonDepartmentID')
                    ->bindValue('gibbonDepartmentID', $gibbonDepartmentID);
            },
        ]);
        
        return $this->runQuery($query, $criteria);
    }

    public function selectRowsByRubric($gibbonRubricID)
    {
        $data = ['gibbonRubricID' => $gibbonRubricID];
        $sql = "SELECT *, (CASE WHEN gibbonOutcome.name IS NOT NULL THEN gibbonOutcome.name ELSE title END) as title FROM gibbonRubricRow LEFT JOIN gibbonOutcome ON (gibbonRubricRow.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID) WHERE gibbonRubricID=:gibbonRubricID ORDER BY sequenceNumber";

        return $this->db()->select($sql, $data);
    }

    public function selectColumnsByRubric($gibbonRubricID)
    {
        $data = ['gibbonRubricID' => $gibbonRubricID];
        $sql = "SELECT * FROM gibbonRubricColumn WHERE gibbonRubricID=:gibbonRubricID ORDER BY sequenceNumber";

        return $this->db()->select($sql, $data);
    }

    public function selectRowsByRubricInSequence($gibbonRubricID)
    {
        $data = ['gibbonRubricID' => $gibbonRubricID];
        $sql = 'SELECT * FROM gibbonRubricRow WHERE gibbonRubricID=:gibbonRubricID ORDER BY sequenceNumber';

        return $this->db()->select($sql, $data);
    }

    public function selectCellsByRubric($gibbonRubricID)
    {
        $data = ['gibbonRubricID' => $gibbonRubricID];
        $sql = "SELECT * FROM gibbonRubricCell WHERE gibbonRubricID=:gibbonRubricID";

        return $this->db()->select($sql, $data);
    }

    public function selectGradeScalesByRubric($gibbonRubricID)
    {
        $data = ['gibbonRubricID' => $gibbonRubricID];
        $sql = "SELECT gibbonScaleGrade.gibbonScaleGradeID, gibbonScaleGrade.*, gibbonScale.name FROM gibbonRubricColumn
        JOIN gibbonScaleGrade ON (gibbonRubricColumn.gibbonScaleGradeID=gibbonScaleGrade.gibbonScaleGradeID)
        JOIN gibbonScale ON (gibbonScale.gibbonScaleID=gibbonScaleGrade.gibbonScaleID)
        WHERE gibbonRubricColumn.gibbonRubricID=:gibbonRubricID";

        return $this->db()->select($sql, $data);
    }
    
    public function selectDistinctRubricCategories()
    {
        $data = [];
        $sql = "SELECT DISTINCT category FROM gibbonRubric ORDER BY category";

        return $this->db()->select($sql, $data);
    }

    public function selectLARubricsByStaffAndDepartment($gibbonRubricID, $gibbonPersonID)
    {
        $data = ['gibbonRubricID' => $gibbonRubricID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT * FROM gibbonRubric JOIN gibbonDepartment ON (gibbonRubric.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) AND NOT gibbonRubric.gibbonDepartmentID IS NULL WHERE gibbonRubricID=:gibbonRubricID AND (role='Coordinator' OR role='Teacher (Curriculum)') AND gibbonPersonID=:gibbonPersonID AND scope='Learning Area'";

        return $this->db()->select($sql, $data);
    }

    public function getColumnByRubricAndColumnID($gibbonRubricID, $gibbonRubricColumnID)
    {
        $data = ['gibbonRubricID' => $gibbonRubricID, 'gibbonRubricColumnID' => $gibbonRubricColumnID];
        $sql = 'SELECT * FROM gibbonRubric JOIN gibbonRubricColumn ON (gibbonRubricColumn.gibbonRubricID=gibbonRubric.gibbonRubricID) WHERE gibbonRubricColumn.gibbonRubricID=:gibbonRubricID AND gibbonRubricColumnID=:gibbonRubricColumnID';

        return $this->db()->selectOne($sql, $data);
    }

    public function getRowByRubricAndRowID($gibbonRubricID, $gibbonRubricRowID)
    {
        $data = ['gibbonRubricID' => $gibbonRubricID, 'gibbonRubricRowID' => $gibbonRubricRowID];
        $sql = 'SELECT * FROM gibbonRubric JOIN gibbonRubricRow ON (gibbonRubricRow.gibbonRubricID=gibbonRubric.gibbonRubricID) WHERE gibbonRubricRow.gibbonRubricID=:gibbonRubricID AND gibbonRubricRowID=:gibbonRubricRowID';

        return $this->db()->selectOne($sql, $data);
    }

    public function selectRowsInfoByRubric($gibbonRubricID)
    {
        $data = ['gibbonRubricID' => $gibbonRubricID];
		$sql = "SELECT gibbonRubricRowID, title, gibbonOutcomeID, backgroundColor FROM gibbonRubricRow WHERE gibbonRubricID=:gibbonRubricID ORDER BY sequenceNumber";
                    
        return $this->db()->select($sql, $data);
    }

    public function selectsColumnsInfoByRubric($gibbonRubricID)
    {
        $data = ['gibbonRubricID' => $gibbonRubricID];
		$sql = "SELECT gibbonRubricColumnID, title, gibbonScaleGradeID, visualise, backgroundColor FROM gibbonRubricColumn WHERE gibbonRubricID=:gibbonRubricID ORDER BY sequenceNumber";

        return $this->db()->select($sql, $data);
    }
}
