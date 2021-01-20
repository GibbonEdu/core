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

namespace Gibbon\Domain\Planner;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * UnitGateway
 *
 * @version v21
 * @since   v21
 */
class UnitGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonUnit';
    private static $primaryKey = 'gibbonUnitID';
    private static $searchableColumns = [];
    
    public function queryUnitsByCourse($criteria, $gibbonCourseID)
    {
        $query = $this
            ->newQuery()
            ->cols([
                'gibbonUnit.gibbonUnitID', 
                'gibbonUnit.name',
                'gibbonUnit.description',
                'gibbonUnit.active',
            ])
            ->from($this->getTableName())
            ->where('gibbonUnit.gibbonCourseID=:gibbonCourseID')
            ->bindValue('gibbonCourseID', $gibbonCourseID);

        $criteria->addFilterRules([
            'active' => function ($query, $active) {
                return $query
                    ->where('gibbonUnit.active = :active')
                    ->bindValue('active', $active);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    

}
