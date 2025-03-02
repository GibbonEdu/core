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

use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\ScrubbableGateway;
use Gibbon\Domain\Traits\Scrubbable;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\Traits\ScrubByPerson;

/**
 * Intervention Contributor Gateway
 *
 * @version v29
 * @since   v29
 */
class INInterventionContributorGateway extends QueryableGateway implements ScrubbableGateway
{
    use TableAware;
    use Scrubbable;
    use ScrubByPerson;

    private static $tableName = 'gibbonINInterventionContributor';
    private static $primaryKey = 'gibbonINInterventionContributorID';
    
    private static $scrubbableKey = 'gibbonPersonIDContributor';
    private static $scrubbableColumns = [];

    /**
     * @param QueryCriteria $criteria
     * @param int $gibbonINInterventionID
     * @return DataSet
     */
    public function queryContributorsByIntervention(QueryCriteria $criteria, $gibbonINInterventionID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonINInterventionContributor.gibbonINInterventionContributorID',
                'gibbonINInterventionContributor.gibbonINInterventionID',
                'gibbonINInterventionContributor.gibbonPersonIDContributor',
                'gibbonINInterventionContributor.type',
                'gibbonINInterventionContributor.timestampCreated',
                'person.title',
                'person.surname',
                'person.preferredName',
                'person.email'
            ])
            ->innerJoin('gibbonPerson AS person', 'gibbonINInterventionContributor.gibbonPersonIDContributor=person.gibbonPersonID')
            ->where('gibbonINInterventionContributor.gibbonINInterventionID = :gibbonINInterventionID')
            ->bindValue('gibbonINInterventionID', $gibbonINInterventionID);

        $criteria->addFilterRules([
            'type' => function ($query, $type) {
                return $query
                    ->where('gibbonINInterventionContributor.type = :type')
                    ->bindValue('type', $type);
            }
        ]);

        return $this->runQuery($query, $criteria);
    }

    /**
     * @param int $gibbonINInterventionContributorID
     * @return array
     */
    public function getContributorByID($gibbonINInterventionContributorID)
    {
        $query = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols([
                'gibbonINInterventionContributor.*',
                'person.title',
                'person.surname',
                'person.preferredName',
                'person.email'
            ])
            ->innerJoin('gibbonPerson AS person', 'gibbonINInterventionContributor.gibbonPersonIDContributor=person.gibbonPersonID')
            ->where('gibbonINInterventionContributor.gibbonINInterventionContributorID = :gibbonINInterventionContributorID')
            ->bindValue('gibbonINInterventionContributorID', $gibbonINInterventionContributorID);

        return $this->runSelect($query)->fetch();
    }

    /**
     * @param int $gibbonINInterventionID
     * @param int $gibbonPersonID
     * @return bool
     */
    public function isContributor($gibbonINInterventionID, $gibbonPersonID)
    {
        $query = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols(['COUNT(*) as count'])
            ->where('gibbonINInterventionID = :gibbonINInterventionID')
            ->bindValue('gibbonINInterventionID', $gibbonINInterventionID)
            ->where('gibbonPersonIDContributor = :gibbonPersonIDContributor')
            ->bindValue('gibbonPersonIDContributor', $gibbonPersonID);

        $result = $this->runSelect($query);
        return ($result && $result->rowCount() > 0 && $result->fetch()['count'] > 0);
    }

    /**
     * @param int $gibbonPersonID
     * @return array
     */
    public function getInterventionIDsByContributor($gibbonPersonID)
    {
        $query = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols(['gibbonINInterventionID'])
            ->where('gibbonPersonIDContributor = :gibbonPersonIDContributor')
            ->bindValue('gibbonPersonIDContributor', $gibbonPersonID);

        $result = $this->runSelect($query);
        return $result->fetchAll(\PDO::FETCH_COLUMN);
    }
}
