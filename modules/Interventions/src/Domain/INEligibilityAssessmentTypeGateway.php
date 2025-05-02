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

namespace Gibbon\Module\Interventions\Domain;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * Eligibility Assessment Type Gateway
 *
 * @version v29
 * @since   v29
 */
class INEligibilityAssessmentTypeGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonINEligibilityAssessmentType';
    private static $primaryKey = 'gibbonINEligibilityAssessmentTypeID';

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryAssessmentTypes(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonINEligibilityAssessmentTypeID', 
                'name', 
                'description', 
                'active'
            ]);

        return $this->runQuery($query, $criteria);
    }

    /**
     * Get all active assessment types
     * 
     * @return array
     */
    public function selectActiveAssessmentTypes()
    {
        $sql = "SELECT gibbonINEligibilityAssessmentTypeID as value, name 
                FROM gibbonINEligibilityAssessmentType 
                WHERE active = 'Y' 
                ORDER BY name";

        return $this->db()->select($sql);
    }
}
