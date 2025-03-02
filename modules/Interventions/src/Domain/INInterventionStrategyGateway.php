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

namespace Gibbon\Domain\Interventions;

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
 * Intervention Strategy Gateway
 *
 * @version v29
 * @since   v29
 */
class INInterventionStrategyGateway extends QueryableGateway implements ScrubbableGateway
{
    use TableAware;
    use Scrubbable;
    use ScrubByPerson;

    private static $tableName = 'gibbonINInterventionStrategy';
    private static $primaryKey = 'gibbonINInterventionStrategyID';
    private static $searchableColumns = ['name', 'description'];
    
    private static $scrubbableKey = 'gibbonPersonIDCreator';
    private static $scrubbableColumns = ['name' => '', 'description' => ''];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryStrategies(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonINInterventionStrategy.gibbonINInterventionStrategyID',
                'gibbonINInterventionStrategy.gibbonINInterventionID',
                'gibbonINInterventionStrategy.name',
                'gibbonINInterventionStrategy.description',
                'gibbonINInterventionStrategy.targetDate',
                'gibbonINInterventionStrategy.status',
                'gibbonINInterventionStrategy.timestampCreated',
                'creator.title',
                'creator.surname as creatorSurname',
                'creator.preferredName as creatorPreferredName'
            ])
            ->innerJoin('gibbonPerson AS creator', 'creator.gibbonPersonID=gibbonINInterventionStrategy.gibbonPersonIDCreator');

        $criteria->addFilterRules([
            'gibbonINInterventionID' => function ($query, $gibbonINInterventionID) {
                return $query
                    ->where('gibbonINInterventionStrategy.gibbonINInterventionID = :gibbonINInterventionID')
                    ->bindValue('gibbonINInterventionID', $gibbonINInterventionID);
            },
            'status' => function ($query, $status) {
                return $query
                    ->where('gibbonINInterventionStrategy.status = :status')
                    ->bindValue('status', $status);
            }
        ]);

        return $this->runQuery($query, $criteria);
    }

    /**
     * @param int $gibbonINInterventionStrategyID
     * @return array
     */
    public function getStrategyByID($gibbonINInterventionStrategyID)
    {
        $query = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols([
                'gibbonINInterventionStrategy.*',
                'creator.title',
                'creator.surname as creatorSurname',
                'creator.preferredName as creatorPreferredName'
            ])
            ->innerJoin('gibbonPerson AS creator', 'creator.gibbonPersonID=gibbonINInterventionStrategy.gibbonPersonIDCreator')
            ->where('gibbonINInterventionStrategy.gibbonINInterventionStrategyID = :gibbonINInterventionStrategyID')
            ->bindValue('gibbonINInterventionStrategyID', $gibbonINInterventionStrategyID);

        return $this->runSelect($query)->fetch();
    }

    /**
     * @param int $gibbonINInterventionID
     * @return array
     */
    public function getStrategiesByInterventionID($gibbonINInterventionID)
    {
        $query = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols([
                'gibbonINInterventionStrategy.*',
                'creator.title',
                'creator.surname as creatorSurname',
                'creator.preferredName as creatorPreferredName'
            ])
            ->innerJoin('gibbonPerson AS creator', 'creator.gibbonPersonID=gibbonINInterventionStrategy.gibbonPersonIDCreator')
            ->where('gibbonINInterventionStrategy.gibbonINInterventionID = :gibbonINInterventionID')
            ->bindValue('gibbonINInterventionID', $gibbonINInterventionID)
            ->orderBy(['gibbonINInterventionStrategy.targetDate', 'gibbonINInterventionStrategy.name']);

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
