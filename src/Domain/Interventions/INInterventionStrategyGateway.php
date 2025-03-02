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

namespace Gibbon\Domain\Interventions;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * Intervention Strategy Gateway
 *
 * @version v29
 * @since   v29
 */
class INInterventionStrategyGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonINInterventionStrategy';
    private static $primaryKey = 'gibbonINInterventionStrategyID';
    private static $searchableColumns = ['gibbonINInterventionStrategy.name'];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryStrategiesByIntervention(QueryCriteria $criteria, $gibbonINInterventionID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonINInterventionStrategy.gibbonINInterventionStrategyID',
                'gibbonINInterventionStrategy.name',
                'gibbonINInterventionStrategy.description',
                'gibbonINInterventionStrategy.targetDate',
                'gibbonINInterventionStrategy.status',
                'gibbonINInterventionStrategy.timestampCreated',
                'creator.title AS creatorTitle',
                'creator.surname AS creatorSurname',
                'creator.preferredName AS creatorPreferredName'
            ])
            ->innerJoin('gibbonPerson AS creator', 'creator.gibbonPersonID=gibbonINInterventionStrategy.gibbonPersonIDCreator')
            ->where('gibbonINInterventionStrategy.gibbonINInterventionID = :gibbonINInterventionID')
            ->bindValue('gibbonINInterventionID', $gibbonINInterventionID);

        return $this->runQuery($query, $criteria);
    }

    /**
     * Get strategy by ID
     * 
     * @param int $gibbonINInterventionStrategyID
     * @return array
     */
    public function getStrategyByID($gibbonINInterventionStrategyID)
    {
        $data = ['gibbonINInterventionStrategyID' => $gibbonINInterventionStrategyID];
        $sql = "SELECT gibbonINInterventionStrategy.*, 
                creator.title AS creatorTitle, creator.surname AS creatorSurname, creator.preferredName AS creatorPreferredName
                FROM gibbonINInterventionStrategy
                JOIN gibbonPerson AS creator ON (creator.gibbonPersonID=gibbonINInterventionStrategy.gibbonPersonIDCreator)
                WHERE gibbonINInterventionStrategy.gibbonINInterventionStrategyID=:gibbonINInterventionStrategyID";

        return $this->db()->selectOne($sql, $data);
    }
}
