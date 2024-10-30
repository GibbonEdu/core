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

namespace Gibbon\Domain\DataUpdater;

use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\ScrubbableGateway;
use Gibbon\Domain\Traits\Scrubbable;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\Traits\ScrubByPerson;

/**
 * @version v16
 * @since   v16
 */
class FinanceUpdateGateway extends QueryableGateway implements ScrubbableGateway
{
    use TableAware;
    use Scrubbable;
    use ScrubByPerson;

    private static $tableName = 'gibbonFinanceInvoiceeUpdate';
    private static $primaryKey = 'gibbonFinanceInvoiceeUpdateID';

    private static $searchableColumns = ['target.surname', 'target.preferredName', 'target.username'];
    
    private static $scrubbableKey = ['gibbonPersonID', 'gibbonFinanceInvoicee', 'gibbonFinanceInvoiceeID'];
    private static $scrubbableColumns = ['companyName' => null,'companyContact' => null,'companyAddress' => null,'companyEmail' => null,'companyCCFamily' => null,'companyPhone' => null,'companyAll' => null];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryDataUpdates(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonFinanceInvoiceeUpdateID', 'gibbonFinanceInvoiceeUpdate.status', 'gibbonFinanceInvoiceeUpdate.timestamp', 'target.preferredName', 'target.surname', 'target.gibbonPersonID as gibbonPersonIDTarget', 'updater.gibbonPersonID as gibbonPersonIDUpdater', 'updater.title as updaterTitle', 'updater.preferredName as updaterPreferredName', 'updater.surname as updaterSurname'
            ])
            ->leftJoin('gibbonFinanceInvoicee', 'gibbonFinanceInvoicee.gibbonFinanceInvoiceeID=gibbonFinanceInvoiceeUpdate.gibbonFinanceInvoiceeID')
            ->leftJoin('gibbonPerson AS target', 'target.gibbonPersonID=gibbonFinanceInvoicee.gibbonPersonID')
            ->leftJoin('gibbonPerson AS updater', 'updater.gibbonPersonID=gibbonFinanceInvoiceeUpdate.gibbonPersonIDUpdater')
            ->where('gibbonFinanceInvoiceeUpdate.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        return $this->runQuery($query, $criteria);
    }
}
