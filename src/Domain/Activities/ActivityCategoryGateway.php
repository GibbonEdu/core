<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

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

namespace Gibbon\Domain\Activities;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

class ActivityCategoryGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonActivityCategory';
    private static $primaryKey = 'gibbonActivityCategoryID';
    private static $searchableColumns = ['gibbonActivityCategory.name','gibbonActivityCategory.nameShort'];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryCategories(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols([
                'gibbonActivityCategory.gibbonActivityCategoryID',
                'gibbonActivityCategory.name',
                'gibbonActivityCategory.nameShort',
                'gibbonActivityCategory.description',
                'gibbonActivityCategory.backgroundImage',
                'gibbonActivityCategory.active',
                'gibbonActivityCategory.viewableDate',
                'gibbonActivityCategory.accessOpenDate',
                'gibbonActivityCategory.accessCloseDate',
                'gibbonActivityCategory.accessEnrolmentDate',
                
                "(CASE WHEN CURRENT_TIMESTAMP >= gibbonActivityCategory.viewableDate THEN 'Y' ELSE 'N' END) as viewable",
                "COUNT(DISTINCT gibbonActivity.gibbonActivityID) as activityCount",
            ])
            ->leftJoin('gibbonActivity', 'gibbonActivityCategory.gibbonActivityCategoryID=gibbonActivity.gibbonActivityCategoryID')
            ->where('gibbonActivityCategory.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->groupBy(['gibbonActivityCategoryID','name']);

        $criteria->addFilterRules([
            'active' => function ($query, $active) {
                return $query
                    ->where('gibbonActivityCategory.active = :active')
                    ->bindValue('active', $active);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryCategoriesByPerson(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonPersonID)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols([
                ':gibbonPersonID as gibbonPersonID',
                'gibbonActivityCategory.gibbonActivityCategoryID',
                'gibbonActivityCategory.name',
                'gibbonActivityCategory.nameShort',
                'gibbonActivityCategory.description',
                'gibbonActivityCategory.backgroundImage',
                'gibbonActivityCategory.active',
                'gibbonActivityCategory.viewableDate',
                'gibbonActivityCategory.accessOpenDate',
                'gibbonActivityCategory.accessCloseDate',
                'gibbonActivityCategory.accessEnrolmentDate',
                "(CASE WHEN CURRENT_TIMESTAMP >= gibbonActivityCategory.viewableDate THEN 'Y' ELSE 'N' END) as viewable",
                "GROUP_CONCAT(DISTINCT gibbonActivity.name ORDER BY deepLearningChoice.choice SEPARATOR ',') as choices",

            ])
            ->leftJoin('deepLearningChoice', 'deepLearningChoice.gibbonActivityCategoryID=gibbonActivityCategory.gibbonActivityCategoryID AND deepLearningChoice.gibbonPersonID=:gibbonPersonID')
            ->leftJoin('gibbonActivity', 'gibbonActivity.gibbonActivityID=deepLearningChoice.gibbonActivityID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->where('gibbonActivityCategory.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonActivityCategory.viewableDate <= CURRENT_TIMESTAMP')
            ->where('gibbonActivityCategory.active ="Y" ')
            ->groupBy(['gibbonActivityCategory.gibbonActivityCategoryID']);

        return $this->runQuery($query, $criteria);
    }

    public function selectAllCategories()
    {
        $sql = "SELECT gibbonSchoolYear.name as groupBy, gibbonActivityCategory.gibbonActivityCategoryID as value, gibbonActivityCategory.name 
                FROM gibbonActivityCategory
                JOIN gibbonSchoolYear ON (gibbonActivityCategory.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) 
                WHERE gibbonActivityCategory.active='Y'
                ORDER BY gibbonSchoolYear.sequenceNumber DESC, gibbonActivityCategory.sequenceNumber, gibbonActivityCategory.name";

        return $this->db()->select($sql);
    }

    public function selectCategoriesBySchoolYear($gibbonSchoolYearID)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID];
        $sql = "SELECT gibbonActivityCategory.gibbonActivityCategoryID as value, gibbonActivityCategory.name 
                FROM gibbonActivityCategory
                WHERE gibbonActivityCategory.active='Y'
                AND gibbonActivityCategory.gibbonSchoolYearID=:gibbonSchoolYearID
                ORDER BY gibbonActivityCategory.sequenceNumber, gibbonActivityCategory.name";

        return $this->db()->select($sql, $data);
    }

    public function getCategoryDetailsByID($gibbonActivityCategoryID)
    {
        $data = ['gibbonActivityCategoryID' => $gibbonActivityCategoryID];
        $sql = "SELECT gibbonActivityCategory.*, gibbonSchoolYear.name as schoolYear, (CASE WHEN CURRENT_TIMESTAMP >= gibbonActivityCategory.viewableDate THEN 'Y' ELSE 'N' END) as viewable
                FROM gibbonActivityCategory
                JOIN gibbonSchoolYear ON (gibbonActivityCategory.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) 
                WHERE gibbonActivityCategory.gibbonActivityCategoryID=:gibbonActivityCategoryID
                GROUP BY gibbonActivityCategory.gibbonActivityCategoryID";

        return $this->db()->selectOne($sql, $data);
    }

    public function getCategorySignUpAccess($gibbonActivityCategoryID, $gibbonPersonID)
    {
        $data = ['gibbonActivityCategoryID' => $gibbonActivityCategoryID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT gibbonStudentEnrolment.gibbonStudentEnrolmentID
                FROM gibbonActivityCategory
                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonActivityCategory.gibbonSchoolYearID)
                WHERE gibbonActivityCategory.gibbonActivityCategoryID=:gibbonActivityCategoryID
                AND gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonID
                GROUP BY gibbonActivityCategory.gibbonActivityCategoryID";

        return $this->db()->selectOne($sql, $data);
    }
}
