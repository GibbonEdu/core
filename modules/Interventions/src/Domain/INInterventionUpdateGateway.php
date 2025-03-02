<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright 2010, Gibbon Foundation
Gibbon, Gibbon Education Ltd. (Hong Kong)

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

namespace Gibbon\Domain\IndividualNeeds;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\Traits\ScrubByPerson;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\DataSet;
use Gibbon\Domain\ScrubbableGateway;
use Gibbon\Domain\Traits\Scrubbable;

/**
 * Intervention Updates Gateway
 *
 * @version v29
 * @since   v29
 */
class INInterventionUpdateGateway extends QueryableGateway implements ScrubbableGateway
{
    use TableAware;
    use Scrubbable;
    use ScrubByPerson;

    private static $tableName = 'gibbonINInterventionUpdate';
    private static $primaryKey = 'gibbonINInterventionUpdateID';

    private static $searchableColumns = [];

    private static $scrubbableKey = 'gibbonPersonID';
    private static $scrubbableColumns = ['comment' => ''];

    /**
     * @param QueryCriteria $criteria
     * @param int $gibbonINInterventionID
     * @return DataSet
     */
    public function queryUpdatesByIntervention(QueryCriteria $criteria, $gibbonINInterventionID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonINInterventionUpdate.*',
                'person.title',
                'person.surname',
                'person.preferredName'
            ])
            ->innerJoin('gibbonPerson AS person', 'gibbonINInterventionUpdate.gibbonPersonID=person.gibbonPersonID')
            ->where('gibbonINInterventionUpdate.gibbonINInterventionID=:gibbonINInterventionID')
            ->bindValue('gibbonINInterventionID', $gibbonINInterventionID)
            ->orderBy(['gibbonINInterventionUpdate.timestamp DESC']);

        return $this->runQuery($query, $criteria);
    }

    public function getUpdateByID($gibbonINInterventionUpdateID)
    {
        $query = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols([
                'gibbonINInterventionUpdate.*',
                'person.title',
                'person.surname',
                'person.preferredName'
            ])
            ->innerJoin('gibbonPerson AS person', 'gibbonINInterventionUpdate.gibbonPersonID=person.gibbonPersonID')
            ->where('gibbonINInterventionUpdate.gibbonINInterventionUpdateID=:gibbonINInterventionUpdateID')
            ->bindValue('gibbonINInterventionUpdateID', $gibbonINInterventionUpdateID);

        return $this->runSelect($query)->fetch();
    }
}
