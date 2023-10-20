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

namespace Gibbon\Module\Reports\Domain;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

class ReportingCriteriaGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonReportingCriteria';
    private static $primaryKey = 'gibbonReportingCriteriaID';
    private static $searchableColumns = ['gibbonReportingCriteria.description'];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryReportingCriteriaGroupsByScope(QueryCriteria $criteria, $gibbonReportingScopeID, $scopeType)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols(['gibbonReportingCriteria.gibbonReportingCriteriaID', 'gibbonReportingCriteria.description', 'gibbonReportingCriteria.target', 'gibbonReportingCriteriaType.name as criteriaType', 'gibbonReportingCriteria.gibbonYearGroupID', 'gibbonReportingCriteria.gibbonFormGroupID', 'gibbonReportingCriteria.gibbonCourseID'])
            ->leftJoin('gibbonReportingCriteriaType', 'gibbonReportingCriteriaType.gibbonReportingCriteriaTypeID=gibbonReportingCriteria.gibbonReportingCriteriaTypeID')
            ->where('gibbonReportingCriteria.gibbonReportingScopeID=:gibbonReportingScopeID')
            ->bindValue('gibbonReportingScopeID', $gibbonReportingScopeID);

        if ($scopeType == 'Year Group') {
            $query->cols(['gibbonYearGroup.gibbonYearGroupID AS scopeTypeID', 'gibbonYearGroup.nameShort as nameShort', 'gibbonYearGroup.name as name', 'COUNT(gibbonReportingCriteria.gibbonReportingCriteriaID) AS count'])
                  ->leftJoin('gibbonYearGroup', 'gibbonYearGroup.gibbonYearGroupID=gibbonReportingCriteria.gibbonYearGroupID')
                  ->groupBy(['gibbonYearGroup.gibbonYearGroupID']);
        } elseif ($scopeType == 'Form Group') {
            $query->cols(['gibbonFormGroup.gibbonFormGroupID AS scopeTypeID', 'gibbonFormGroup.nameShort as nameShort', 'gibbonFormGroup.name as name', 'COUNT(gibbonReportingCriteria.gibbonReportingCriteriaID) AS count'])
                  ->leftJoin('gibbonFormGroup', 'gibbonFormGroup.gibbonFormGroupID=gibbonReportingCriteria.gibbonFormGroupID')
                  ->groupBy(['gibbonFormGroup.gibbonFormGroupID']);
        } elseif ($scopeType == 'Course') {
            $query->cols(['gibbonCourse.gibbonCourseID AS scopeTypeID', 'gibbonCourse.nameShort as nameShort', 'gibbonCourse.name as name', 'COUNT(gibbonReportingCriteria.gibbonReportingCriteriaID) AS count'])
                  ->leftJoin('gibbonCourse', 'gibbonCourse.gibbonCourseID=gibbonReportingCriteria.gibbonCourseID')
                  ->groupBy(['gibbonCourse.gibbonCourseID']);
        }

        return $this->runQuery($query, $criteria);
    }

    public function queryReportingCriteriaGroupsByCycle(QueryCriteria $criteria, $gibbonReportingCycleID)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonReportingCriteria')
            ->cols(['gibbonReportingScope.name as scopeName', 'gibbonReportingScope.sequenceNumber', "CONCAT(gibbonReportingScope.gibbonReportingScopeID, '-', gibbonYearGroup.gibbonYearGroupID) as value", 'gibbonYearGroup.nameShort as name', 'gibbonYearGroup.sequenceNumber as nameOrder'])
            ->innerJoin('gibbonReportingScope', 'gibbonReportingScope.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID')
            ->innerJoin('gibbonYearGroup', 'gibbonYearGroup.gibbonYearGroupID=gibbonReportingCriteria.gibbonYearGroupID')
            ->where('gibbonReportingScope.gibbonReportingCycleID=:gibbonReportingCycleID')
            ->where("gibbonReportingScope.scopeType = 'Year Group'")
            ->bindValue('gibbonReportingCycleID', $gibbonReportingCycleID)
            ->groupBy(['gibbonReportingScope.gibbonReportingScopeID', 'gibbonYearGroup.gibbonYearGroupID']);

        $query->unionAll()
            ->from('gibbonReportingCriteria')
            ->cols(['gibbonReportingScope.name as scopeName', 'gibbonReportingScope.sequenceNumber', "CONCAT(gibbonReportingScope.gibbonReportingScopeID, '-', gibbonFormGroup.gibbonFormGroupID) as value", 'gibbonFormGroup.nameShort as name', 'gibbonFormGroup.nameShort as nameOrder'])
            ->innerJoin('gibbonReportingScope', 'gibbonReportingScope.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID')
            ->innerJoin('gibbonFormGroup', 'gibbonFormGroup.gibbonFormGroupID=gibbonReportingCriteria.gibbonFormGroupID')
            ->where('gibbonReportingScope.gibbonReportingCycleID=:gibbonReportingCycleID')
            ->where("gibbonReportingScope.scopeType = 'Form Group'")
            ->bindValue('gibbonReportingCycleID', $gibbonReportingCycleID)
            ->groupBy(['gibbonReportingScope.gibbonReportingScopeID', 'gibbonFormGroup.gibbonFormGroupID']);

        $query->unionAll()
            ->from('gibbonReportingCriteria')
            ->cols(['gibbonReportingScope.name as scopeName', 'gibbonReportingScope.sequenceNumber', "CONCAT(gibbonReportingScope.gibbonReportingScopeID, '-', gibbonCourseClass.gibbonCourseClassID) as value", "CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) as name", "CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) as nameOrder"])
            ->innerJoin('gibbonReportingScope', 'gibbonReportingScope.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID')
            ->innerJoin('gibbonCourse', 'gibbonCourse.gibbonCourseID=gibbonReportingCriteria.gibbonCourseID')
            ->innerJoin('gibbonCourseClass', 'gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID')
            ->where('gibbonReportingScope.gibbonReportingCycleID=:gibbonReportingCycleID')
            ->where("gibbonReportingScope.scopeType = 'Course'")
            ->bindValue('gibbonReportingCycleID', $gibbonReportingCycleID)
            ->groupBy(['gibbonReportingScope.gibbonReportingScopeID', 'gibbonCourseClass.gibbonCourseClassID']);

        return $this->runQuery($query, $criteria);
    }

    public function queryReportingCriteriaByScope(QueryCriteria $criteria, $gibbonReportingScopeID, $scopeType, $scopeTypeID = null)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols(['gibbonReportingCriteria.gibbonReportingCriteriaID', 'gibbonReportingCriteria.name', 'gibbonReportingCriteria.description', 'gibbonReportingCriteria.target', 'gibbonReportingCriteriaType.name as criteriaType', 'gibbonReportingCriteria.gibbonYearGroupID', 'gibbonReportingCriteria.gibbonFormGroupID', 'gibbonReportingCriteria.gibbonCourseID', "COUNT(DISTINCT CASE WHEN gibbonReportingValueID IS NOT NULL THEN gibbonReportingValueID END) as values"])
            ->leftJoin('gibbonReportingCriteriaType', 'gibbonReportingCriteriaType.gibbonReportingCriteriaTypeID=gibbonReportingCriteria.gibbonReportingCriteriaTypeID')
            ->leftJoin('gibbonReportingValue', 'gibbonReportingValue.gibbonReportingCriteriaID=gibbonReportingCriteria.gibbonReportingCriteriaID')
            ->where('gibbonReportingCriteria.gibbonReportingScopeID=:gibbonReportingScopeID')
            ->bindValue('gibbonReportingScopeID', $gibbonReportingScopeID)
            ->groupBy(['gibbonReportingCriteria.gibbonReportingCriteriaID']);

        if ($scopeType == 'Year Group') {
            $query->cols(['gibbonYearGroup.nameShort as scopeTypeName', 'gibbonYearGroup.sequenceNumber as scopeSequence'])
                ->innerJoin('gibbonYearGroup', 'gibbonYearGroup.gibbonYearGroupID=gibbonReportingCriteria.gibbonYearGroupID');

            if (!empty($scopeTypeID)) {
                $query->where('gibbonReportingCriteria.gibbonYearGroupID=:gibbonYearGroupID', ['gibbonYearGroupID' => $scopeTypeID]);
            }
        } else if ($scopeType == 'Form Group') {
            $query->cols(['gibbonFormGroup.nameShort as scopeTypeName', 'gibbonFormGroup.nameShort as scopeSequence'])
                ->innerJoin('gibbonFormGroup', 'gibbonFormGroup.gibbonFormGroupID=gibbonReportingCriteria.gibbonFormGroupID');

            if (!empty($scopeTypeID)) {
                $query->where('gibbonReportingCriteria.gibbonFormGroupID=:gibbonFormGroupID', ['gibbonFormGroupID' => $scopeTypeID]);
            }
        } else if ($scopeType == 'Course') {
            $query->cols(['gibbonCourse.nameShort as scopeTypeName', 'gibbonCourse.nameShort as scopeSequence'])
                  ->innerJoin('gibbonCourse', 'gibbonCourse.gibbonCourseID=gibbonReportingCriteria.gibbonCourseID');

            if (!empty($scopeTypeID)) {
                $query->where('gibbonReportingCriteria.gibbonCourseID=:gibbonCourseID', ['gibbonCourseID' => $scopeTypeID]);
            }
        }
        return $this->runQuery($query, $criteria);
    }

    public function getCriteriaTypeByID($gibbonReportingCriteriaID)
    {
        $data = ['gibbonReportingCriteriaID' => $gibbonReportingCriteriaID];
        $sql = "SELECT gibbonReportingCriteriaType.* 
                FROM gibbonReportingCriteriaType 
                JOIN gibbonReportingCriteria ON (gibbonReportingCriteria.gibbonReportingCriteriaTypeID=gibbonReportingCriteriaType.gibbonReportingCriteriaTypeID)
                WHERE gibbonReportingCriteria.gibbonReportingCriteriaID=:gibbonReportingCriteriaID";

        return $this->db()->selectOne($sql, $data);
    }

    public function getHighestSequenceNumberByScope($gibbonReportingScopeID, $scopeType, $scopeTypeID)
    {
        $query = $this
            ->newSelect()
            ->from('gibbonReportingScope')
            ->cols(['MAX(gibbonReportingCriteria.sequenceNumber) as sequenceNumber', 'COUNT(gibbonReportingCriteria.gibbonReportingCriteriaID) as count'])
            ->innerJoin('gibbonReportingCriteria', 'gibbonReportingCriteria.gibbonReportingScopeID=gibbonReportingScope.gibbonReportingScopeID')
            ->where('gibbonReportingScope.gibbonReportingScopeID=:gibbonReportingScopeID')
            ->bindValue('gibbonReportingScopeID', $gibbonReportingScopeID)
            ->groupBy(['gibbonReportingScope.gibbonReportingScopeID']);

        if ($scopeType == 'Year Group') {
            $query->where('gibbonReportingCriteria.gibbonYearGroupID=:scopeTypeID')
                  ->bindValue('scopeTypeID', $scopeTypeID);
        } elseif ($scopeType == 'Form Group') {
            $query->where('gibbonReportingCriteria.gibbonFormGroupID=:scopeTypeID')
                  ->bindValue('scopeTypeID', $scopeTypeID);
        } elseif ($scopeType == 'Course') {
            $query->where('gibbonReportingCriteria.gibbonCourseID=:scopeTypeID')
                  ->bindValue('scopeTypeID', $scopeTypeID);
        }

        return $this->runSelect($query)->fetchColumn(0) ?? 0;
    }
}
