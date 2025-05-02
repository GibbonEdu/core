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

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * Support Plan Gateway
 *
 * @version v1.0
 * @since   v1.0
 */
class INSupportPlanGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonINSupportPlan';
    private static $primaryKey = 'gibbonINSupportPlanID';
    private static $searchableColumns = ['gibbonINSupportPlan.name', 'gibbonINSupportPlan.description', 'gibbonINSupportPlan.goals', 'gibbonINSupportPlan.strategies'];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function querySupportPlans(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonINSupportPlan.gibbonINSupportPlanID', 
                'gibbonINSupportPlan.gibbonINInterventionID', 
                'gibbonINSupportPlan.name', 
                'gibbonINSupportPlan.description',
                'gibbonINSupportPlan.goals',
                'gibbonINSupportPlan.strategies',
                'gibbonINSupportPlan.resources',
                'gibbonINSupportPlan.targetDate',
                'gibbonINSupportPlan.gibbonPersonIDStaff',
                'gibbonINSupportPlan.dateStart',
                'gibbonINSupportPlan.dateEnd',
                'gibbonINSupportPlan.status',
                'gibbonINSupportPlan.outcome',
                'gibbonINSupportPlan.outcomeNotes',
                'gibbonINSupportPlan.timestampCreated',
                'gibbonINSupportPlan.timestampModified',
                'gibbonPerson.title', 
                'gibbonPerson.surname', 
                'gibbonPerson.preferredName',
                'creator.title AS creatorTitle', 
                'creator.surname AS creatorSurname', 
                'creator.preferredName AS creatorPreferredName'
            ])
            ->leftJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonINSupportPlan.gibbonPersonIDStaff')
            ->leftJoin('gibbonPerson AS creator', 'creator.gibbonPersonID=gibbonINSupportPlan.gibbonPersonIDCreator');

        $criteria->addFilterRules([
            'gibbonINInterventionID' => function ($query, $gibbonINInterventionID) {
                return $query
                    ->where('gibbonINSupportPlan.gibbonINInterventionID = :gibbonINInterventionID')
                    ->bindValue('gibbonINInterventionID', $gibbonINInterventionID);
            },
            'status' => function ($query, $status) {
                return $query
                    ->where('gibbonINSupportPlan.status = :status')
                    ->bindValue('status', $status);
            },
            'active' => function ($query, $active) {
                if ($active == 'Y') {
                    return $query->where("gibbonINSupportPlan.status = 'Active'");
                } else {
                    return $query;
                }
            }
        ]);

        // Debug the SQL query
        $sql = $query->getStatement();
        error_log('DEBUG SQL: ' . $sql);
        
        return $this->runQuery($query, $criteria);
    }
    
    /**
     * Get support plan by ID
     *
     * @param string $gibbonINSupportPlanID
     * @return array
     */
    public function getByID($gibbonINSupportPlanID)
    {
        $data = ['gibbonINSupportPlanID' => $gibbonINSupportPlanID];
        $sql = "SELECT gibbonINSupportPlan.*, 
                    staff.title as staffTitle, staff.preferredName as staffPreferredName, staff.surname as staffSurname,
                    creator.title as creatorTitle, creator.preferredName as creatorPreferredName, creator.surname as creatorSurname
                FROM gibbonINSupportPlan
                LEFT JOIN gibbonPerson as staff ON (staff.gibbonPersonID=gibbonINSupportPlan.gibbonPersonIDStaff)
                LEFT JOIN gibbonPerson as creator ON (creator.gibbonPersonID=gibbonINSupportPlan.gibbonPersonIDCreator)
                WHERE gibbonINSupportPlanID=:gibbonINSupportPlanID";

        return $this->db()->selectOne($sql, $data);
    }
    
    /**
     * Get active support plan for an intervention
     *
     * @param string $gibbonINInterventionID
     * @return array
     */
    public function getActiveSupportPlan($gibbonINInterventionID)
    {
        $data = ['gibbonINInterventionID' => $gibbonINInterventionID];
        $sql = "SELECT gibbonINSupportPlan.*, 
                    staff.title as staffTitle, staff.preferredName as staffPreferredName, staff.surname as staffSurname,
                    creator.title as creatorTitle, creator.preferredName as creatorPreferredName, creator.surname as creatorSurname
                FROM gibbonINSupportPlan
                LEFT JOIN gibbonPerson as staff ON (staff.gibbonPersonID=gibbonINSupportPlan.gibbonPersonIDStaff)
                LEFT JOIN gibbonPerson as creator ON (creator.gibbonPersonID=gibbonINSupportPlan.gibbonPersonIDCreator)
                WHERE gibbonINInterventionID=:gibbonINInterventionID
                AND gibbonINSupportPlan.status='Active'";

        return $this->db()->selectOne($sql, $data);
    }
    
    /**
     * Get all support plans for an intervention
     *
     * @param string $gibbonINInterventionID
     * @return array
     */
    public function getSupportPlansByIntervention($gibbonINInterventionID)
    {
        $data = ['gibbonINInterventionID' => $gibbonINInterventionID];
        $sql = "SELECT gibbonINSupportPlan.*, 
                    staff.title as staffTitle, staff.preferredName as staffPreferredName, staff.surname as staffSurname,
                    creator.title as creatorTitle, creator.preferredName as creatorPreferredName, creator.surname as creatorSurname
                FROM gibbonINSupportPlan
                LEFT JOIN gibbonPerson as staff ON (staff.gibbonPersonID=gibbonINSupportPlan.gibbonPersonIDStaff)
                LEFT JOIN gibbonPerson as creator ON (creator.gibbonPersonID=gibbonINSupportPlan.gibbonPersonIDCreator)
                WHERE gibbonINInterventionID=:gibbonINInterventionID
                ORDER BY gibbonINSupportPlan.status='Active' DESC, gibbonINSupportPlan.timestampCreated DESC";

        return $this->db()->select($sql, $data);
    }
}
