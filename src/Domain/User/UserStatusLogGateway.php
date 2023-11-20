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

namespace Gibbon\Domain\User;

use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\Traits\TableAware;

/**
 * User Status Log Gateway
 *
 * @version v23
 * @since   v23
 */
class UserStatusLogGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonPersonStatusLog';
    private static $primaryKey = 'gibbonPersonStatusLogID';

    private static $searchableColumns = [''];


    public function queryStatusLogByPerson(QueryCriteria $criteria, $gibbonPersonID) {
        $query = $this
            ->newQuery()
            ->cols(['gibbonPersonStatusLogID', 'gibbonPersonID', 'statusOld', 'statusNew', 'reason', 'timestamp'])
            ->from('gibbonPersonStatusLog')
            ->where('gibbonPersonStatusLog.gibbonPersonID = :gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID);
            
        return $this->runQuery($query, $criteria);
    }
}
