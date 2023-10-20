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

namespace Gibbon\Domain\Messenger;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * CannedResponseGateway
 *
 * @version v21
 * @since   v21
 */
class CannedResponseGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonMessengerCannedResponse';
    private static $primaryKey = 'gibbonMessengerCannedResponseID';
    private static $searchableColumns = ['gibbonMessengerCannedResponse.subject'];
    
    /**
     * Queries the list of groups for the messenger Canned Response page.
     *
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryCannedResponses(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonMessengerCannedResponse.*'
            ]);
        
        return $this->runQuery($query, $criteria);
    }

    public function selectCannedResponses()
    {
        $sql = "SELECT * FROM gibbonMessengerCannedResponse ORDER BY subject";
        
        return $this->db()->select($sql);
    }
}
