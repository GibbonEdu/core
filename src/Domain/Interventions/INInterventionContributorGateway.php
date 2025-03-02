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
 * Intervention Contributor Gateway
 *
 * @version v29
 * @since   v29
 */
class INInterventionContributorGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonINInterventionContributor';
    private static $primaryKey = 'gibbonINInterventionContributorID';
    private static $searchableColumns = [];

    /**
     * @param QueryCriteria $criteria
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
                'contributor.title',
                'contributor.surname',
                'contributor.preferredName',
                'contributor.email',
                'contributor.phone1',
                'contributor.phone2',
                'contributor.phone3',
                'contributor.phone4'
            ])
            ->innerJoin('gibbonPerson AS contributor', 'contributor.gibbonPersonID=gibbonINInterventionContributor.gibbonPersonIDContributor')
            ->where('gibbonINInterventionContributor.gibbonINInterventionID = :gibbonINInterventionID')
            ->bindValue('gibbonINInterventionID', $gibbonINInterventionID);

        return $this->runQuery($query, $criteria);
    }

    /**
     * Get contributor by ID
     * 
     * @param int $gibbonINInterventionContributorID
     * @return array
     */
    public function getContributorByID($gibbonINInterventionContributorID)
    {
        $data = ['gibbonINInterventionContributorID' => $gibbonINInterventionContributorID];
        $sql = "SELECT gibbonINInterventionContributor.*, 
                contributor.title, contributor.surname, contributor.preferredName,
                contributor.email, contributor.phone1, contributor.phone2, contributor.phone3, contributor.phone4
                FROM gibbonINInterventionContributor
                JOIN gibbonPerson AS contributor ON (contributor.gibbonPersonID=gibbonINInterventionContributor.gibbonPersonIDContributor)
                WHERE gibbonINInterventionContributor.gibbonINInterventionContributorID=:gibbonINInterventionContributorID";

        return $this->db()->selectOne($sql, $data);
    }

    /**
     * Check if a person is already a contributor to an intervention
     * 
     * @param int $gibbonINInterventionID
     * @param int $gibbonPersonIDContributor
     * @return bool
     */
    public function isContributor($gibbonINInterventionID, $gibbonPersonIDContributor)
    {
        $data = [
            'gibbonINInterventionID' => $gibbonINInterventionID,
            'gibbonPersonIDContributor' => $gibbonPersonIDContributor
        ];
        
        $sql = "SELECT COUNT(*) FROM gibbonINInterventionContributor 
                WHERE gibbonINInterventionID=:gibbonINInterventionID 
                AND gibbonPersonIDContributor=:gibbonPersonIDContributor";
        
        return $this->db()->selectOne($sql, $data)['COUNT(*)'] > 0;
    }

    /**
     * Delete a contributor
     * 
     * @param int $gibbonINInterventionContributorID
     * @return bool
     */
    public function deleteContributor($gibbonINInterventionContributorID)
    {
        $data = ['gibbonINInterventionContributorID' => $gibbonINInterventionContributorID];
        
        $sql = "DELETE FROM gibbonINInterventionContributor 
                WHERE gibbonINInterventionContributorID=:gibbonINInterventionContributorID";
        
        return $this->db()->delete($sql, $data);
    }
}
