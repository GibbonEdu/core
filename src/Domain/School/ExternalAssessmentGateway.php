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

namespace Gibbon\Domain\School;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * @version v17
 * @since   v17
 */
class ExternalAssessmentGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonExternalAssessment';

    private static $searchableColumns = ['name'];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryExternalAssessments(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonExternalAssessmentID', 'name', 'description', 'active', 'allowFileUpload'
            ]);

        return $this->runQuery($query, $criteria);
    }

    public function queryExternalAssessmentFields(QueryCriteria $criteria, $gibbonExternalAssessmentID)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonExternalAssessmentField')
            ->cols([
                'gibbonExternalAssessmentFieldID', 'gibbonExternalAssessmentID', 'name', 'category', 'gibbonExternalAssessmentField.order'
            ])
            ->where('gibbonExternalAssessmentField.gibbonExternalAssessmentID = :gibbonExternalAssessmentID')
            ->bindValue('gibbonExternalAssessmentID', $gibbonExternalAssessmentID);

        return $this->runQuery($query, $criteria);
    }
}
