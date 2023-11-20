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

namespace Gibbon\Domain\System;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * String Repalcement Gateway
 *
 * @version v16
 * @since   v16
 */
class StringGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonString';
    private static $primaryKey = 'gibbonStringID';

    private static $searchableColumns = ['original', 'replacement'];
    
    /**
     * Queries the list of strings for the Manage String Replacements page.
     *
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryStrings(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonStringID', 'original', 'replacement', 'mode', 'caseSensitive', 'priority'
            ]);

        return $this->runQuery($query, $criteria);
    }
}
