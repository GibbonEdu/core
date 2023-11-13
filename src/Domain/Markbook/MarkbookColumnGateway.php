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
 * Markbook Column Gateway
 *
 * @version v17
 * @since   v17
 */
class MarkbookColumnGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonMarkbookColumn';
    private static $primaryKey = 'gibbonMarkbookColumnID';
    private static $searchableColumns = ['name', 'description', 'type'];
    
    /**
     * 
     */
    public function queryMarkbookColumnsByClass(QueryCriteria $criteria, $gibbonCourseClassID)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonMarkbookColumn')
            ->cols(['*','gibbonMarkbookColumn.name as name','gibbonMarkbookColumn.sequenceNumber as sequenceNumber'])
            ->where('gibbonMarkbookColumn.gibbonCourseClassID = :gibbonCourseClassID')
            ->bindValue('gibbonCourseClassID', $gibbonCourseClassID)
            ->groupBy(['gibbonMarkbookColumn.gibbonMarkbookColumnID']);

        $criteria->addFilterRules([
            'term' => function ($query, $gibbonSchoolYearTermID) {
                if (intval($gibbonSchoolYearTermID) <= 0) return $query;

                return $query
                    ->innerJoin('gibbonSchoolYearTerm', 'gibbonSchoolYearTerm.gibbonSchoolYearTermID=gibbonMarkbookColumn.gibbonSchoolYearTermID 
                        OR gibbonMarkbookColumn.date BETWEEN gibbonSchoolYearTerm.firstDay AND gibbonSchoolYearTerm.lastDay')
                    ->where('gibbonSchoolYearTerm.gibbonSchoolYearTermID = :gibbonSchoolYearTermID')
                    ->bindValue('gibbonSchoolYearTermID', $gibbonSchoolYearTermID);
            },
            'show' => function ($query, $show) {
                switch ($show) {
                    case 'marked'  : $query->where("complete = 'Y'"); break;
                    case 'unmarked': $query->where("complete = 'N'"); break;
                }
                return $query;
            },
        ]);

        return $this->runQuery($query, $criteria);
    }
}
