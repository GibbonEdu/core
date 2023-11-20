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

namespace Gibbon\Domain\Staff;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * Staff Gateway
 *
 * @version v20
 * @since   v20
 */
class StaffContractGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonStaffContract';
    private static $primaryKey = 'gibbonStaffContractID';

    private static $searchableColumns = ['gibbonStaffID', 'dateStart'];

    /**
     * Queries the list of contracts by a Staff's ID.
     *
     * @param QueryCriteria $criteria
     * @param $gibbonStaffID
     * @return DataSet
     */
    public function queryContractsByStaff(QueryCriteria $criteria, $gibbonStaffID) {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonStaffContract.gibbonStaffContractID', 'gibbonStaffContract.gibbonStaffID', 'gibbonStaffContract.title', 'gibbonStaffContract.status', 'gibbonStaffContract.dateStart', 'gibbonStaffContract.dateEnd'
            ])
            ->where('gibbonStaffContract.gibbonStaffID = :gibbonStaffID')
            ->bindValue('gibbonStaffID', $gibbonStaffID);

        return $this->runQuery($query, $criteria);
    }
}
