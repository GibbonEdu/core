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

namespace Gibbon\Module\Interventions\Domain;

use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\ScrubbableGateway;
use Gibbon\Domain\Traits\Scrubbable;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\Traits\ScrubByPerson;
use Gibbon\Domain\DataSet;
use Aura\SqlQuery\Common\DeleteInterface;
use Aura\SqlQuery\Common\UpdateInterface;

/**
 * Intervention Outcome Gateway
 *
 * @version v29
 * @since   v29
 */
class INInterventionOutcomeGateway extends QueryableGateway implements ScrubbableGateway
{
    use TableAware;
    use Scrubbable;
    use ScrubByPerson;

    private static $tableName = 'gibbonINInterventionOutcome';
    private static $primaryKey = 'gibbonINInterventionOutcomeID';
    private static $searchableColumns = ['outcome', 'evidence'];
    
    private static $scrubbableKey = 'gibbonPersonIDCreator';
    private static $scrubbableColumns = ['outcome' => '', 'evidence' => ''];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryOutcomes(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonINInterventionOutcome.gibbonINInterventionOutcomeID',
                'gibbonINInterventionOutcome.gibbonINInterventionStrategyID',
                'gibbonINInterventionOutcome.outcome',
                'gibbonINInterventionOutcome.evidence',
                'gibbonINInterventionOutcome.successful',
                'gibbonINInterventionOutcome.timestampCreated',
                'strategy.name as strategyName',
                'creator.title',
                'creator.surname as creatorSurname',
                'creator.preferredName as creatorPreferredName'
            ])
            ->innerJoin('gibbonINInterventionStrategy AS strategy', 'strategy.gibbonINInterventionStrategyID=gibbonINInterventionOutcome.gibbonINInterventionStrategyID')
            ->innerJoin('gibbonPerson AS creator', 'creator.gibbonPersonID=gibbonINInterventionOutcome.gibbonPersonIDCreator');

        $criteria->addFilterRules([
            'gibbonINInterventionStrategyID' => function ($query, $gibbonINInterventionStrategyID) {
                return $query
                    ->where('gibbonINInterventionOutcome.gibbonINInterventionStrategyID = :gibbonINInterventionStrategyID')
                    ->bindValue('gibbonINInterventionStrategyID', $gibbonINInterventionStrategyID);
            },
            'successful' => function ($query, $successful) {
                return $query
                    ->where('gibbonINInterventionOutcome.successful = :successful')
                    ->bindValue('successful', $successful);
            }
        ]);

        return $this->runQuery($query, $criteria);
    }

    /**
     * @param int $gibbonINInterventionOutcomeID
     * @return array
     */
    public function getOutcomeByID($gibbonINInterventionOutcomeID)
    {
        $query = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols([
                'gibbonINInterventionOutcome.*',
                'strategy.name as strategyName',
                'creator.title',
                'creator.surname as creatorSurname',
                'creator.preferredName as creatorPreferredName'
            ])
            ->innerJoin('gibbonINInterventionStrategy AS strategy', 'strategy.gibbonINInterventionStrategyID=gibbonINInterventionOutcome.gibbonINInterventionStrategyID')
            ->innerJoin('gibbonPerson AS creator', 'creator.gibbonPersonID=gibbonINInterventionOutcome.gibbonPersonIDCreator')
            ->where('gibbonINInterventionOutcome.gibbonINInterventionOutcomeID = :gibbonINInterventionOutcomeID')
            ->bindValue('gibbonINInterventionOutcomeID', $gibbonINInterventionOutcomeID);

        return $this->runSelect($query)->fetch();
    }

    /**
     * @param int $gibbonINInterventionStrategyID
     * @return array
     */
    public function getOutcomesByStrategyID($gibbonINInterventionStrategyID)
    {
        $query = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols([
                'gibbonINInterventionOutcome.*',
                'creator.title',
                'creator.surname as creatorSurname',
                'creator.preferredName as creatorPreferredName'
            ])
            ->innerJoin('gibbonPerson AS creator', 'creator.gibbonPersonID=gibbonINInterventionOutcome.gibbonPersonIDCreator')
            ->where('gibbonINInterventionOutcome.gibbonINInterventionStrategyID = :gibbonINInterventionStrategyID')
            ->bindValue('gibbonINInterventionStrategyID', $gibbonINInterventionStrategyID)
            ->orderBy(['gibbonINInterventionOutcome.timestampCreated DESC']);

        return $this->runSelect($query)->fetchAll();
    }

    protected function runUpdate(UpdateInterface $query) : bool
    {
        return $this->db()->update($query->getStatement(), $query->getBindValues());
    }

    protected function runDelete(DeleteInterface $query) : bool
    {
        return $this->db()->delete($query->getStatement(), $query->getBindValues());
    }
}
