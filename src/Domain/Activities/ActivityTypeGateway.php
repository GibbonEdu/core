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

/**
 * Activity Type Gateway
 *
 * @version v23
 * @since   v23
 */
class ActivityTypeGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonActivityType';
    private static $primaryKey = 'gibbonActivityTypeID';

    private static $searchableColumns = ['gibbonActivityType.name'];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryActivityTypes(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonActivityType.*',
            ]);


        return $this->runQuery($query, $criteria);
    }

    public function selectActivityTypeOptions()
    {
        $sql = "SELECT name as value, name FROM gibbonActivityType ORDER BY name";

        return $this->db()->select($sql);
    }
}
