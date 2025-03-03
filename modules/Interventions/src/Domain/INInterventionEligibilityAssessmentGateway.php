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
use Aura\SqlQuery\Common\InsertInterface;

/**
 * Intervention Eligibility Assessment Gateway
 *
 * @version v29
 * @since   v29
 */
class INInterventionEligibilityAssessmentGateway extends QueryableGateway implements ScrubbableGateway
{
    use TableAware;
    use Scrubbable;
    use ScrubByPerson;

    private static $tableName = 'gibbonINInterventionEligibilityAssessment';
    private static $primaryKey = 'gibbonINInterventionEligibilityAssessmentID';

    private static $searchableColumns = [];

    private static $scrubbableKey = 'gibbonPersonIDCreator';
    private static $scrubbableColumns = ['notes' => null];

    /**
     * @param QueryCriteria $criteria
     * @param int $gibbonINInterventionID
     * @return DataSet
     */
    public function queryAssessmentsByIntervention(QueryCriteria $criteria, $gibbonINInterventionID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonINInterventionEligibilityAssessment.*',
                'creator.title',
                'creator.surname',
                'creator.preferredName'
            ])
            ->leftJoin('gibbonPerson AS creator', 'gibbonINInterventionEligibilityAssessment.gibbonPersonIDCreator=creator.gibbonPersonID')
            ->where('gibbonINInterventionEligibilityAssessment.gibbonINInterventionID=:gibbonINInterventionID')
            ->bindValue('gibbonINInterventionID', $gibbonINInterventionID);

        return $this->runQuery($query, $criteria);
    }

    /**
     * @param QueryCriteria $criteria
     * @param int $gibbonPersonID
     * @return DataSet
     */
    public function queryAssessmentsByStudent(QueryCriteria $criteria, $gibbonPersonID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonINInterventionEligibilityAssessment.*',
                'creator.title',
                'creator.surname',
                'creator.preferredName'
            ])
            ->leftJoin('gibbonPerson AS creator', 'gibbonINInterventionEligibilityAssessment.gibbonPersonIDCreator=creator.gibbonPersonID')
            ->where('gibbonINInterventionEligibilityAssessment.gibbonPersonIDStudent=:gibbonPersonIDStudent')
            ->bindValue('gibbonPersonIDStudent', $gibbonPersonID);

        return $this->runQuery($query, $criteria);
    }

    /**
     * Get a single eligibility assessment by intervention ID
     *
     * @param int $gibbonINInterventionID
     * @return array|null The assessment record or null if not found
     */
    public function getByInterventionID($gibbonINInterventionID)
    {
        $query = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols([
                'gibbonINInterventionEligibilityAssessment.*',
                'creator.title',
                'creator.surname',
                'creator.preferredName'
            ])
            ->leftJoin('gibbonPerson AS creator', 'gibbonINInterventionEligibilityAssessment.gibbonPersonIDCreator=creator.gibbonPersonID')
            ->where('gibbonINInterventionEligibilityAssessment.gibbonINInterventionID=:gibbonINInterventionID')
            ->bindValue('gibbonINInterventionID', $gibbonINInterventionID);

        return $this->runSelect($query)->fetch();
    }
    
    protected function runInsert(InsertInterface $query)
    {
        return $this->db()->insert($query->getStatement(), $query->getBindValues());
    }
    
    protected function runUpdate(UpdateInterface $query) : bool
    {
        return $this->db()->update($query->getStatement(), $query->getBindValues());
    }

    protected function runDelete(DeleteInterface $query) : bool
    {
        return $this->db()->delete($query->getStatement(), $query->getBindValues());
    }
    
    /**
     * Get all eligibility assessments for a student
     *
     * @param int $gibbonPersonID
     * @return array The assessment records
     */
    public function getByStudentID($gibbonPersonID)
    {
        $query = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols([
                'gibbonINInterventionEligibilityAssessment.*',
                'creator.title',
                'creator.surname',
                'creator.preferredName',
                'intervention.name as interventionName'
            ])
            ->leftJoin('gibbonPerson AS creator', 'gibbonINInterventionEligibilityAssessment.gibbonPersonIDCreator=creator.gibbonPersonID')
            ->leftJoin('gibbonINIntervention AS intervention', 'gibbonINInterventionEligibilityAssessment.gibbonINInterventionID=intervention.gibbonINInterventionID')
            ->where('gibbonINInterventionEligibilityAssessment.gibbonPersonIDStudent=:gibbonPersonIDStudent')
            ->bindValue('gibbonPersonIDStudent', $gibbonPersonID);

        return $this->runSelect($query)->fetchAll();
    }
}
