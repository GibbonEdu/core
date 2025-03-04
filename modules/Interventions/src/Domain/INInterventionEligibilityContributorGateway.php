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
use Aura\SqlQuery\Common\InsertInterface;

/**
 * Intervention Eligibility Contributor Gateway
 *
 * @version v29
 * @since   v29
 */
class INInterventionEligibilityContributorGateway extends QueryableGateway implements ScrubbableGateway
{
    use TableAware;
    use Scrubbable;
    use ScrubByPerson;

    private static $tableName = 'gibbonINInterventionEligibilityContributor';
    private static $primaryKey = 'gibbonINInterventionEligibilityContributorID';

    private static $searchableColumns = [];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryContributors(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonINInterventionEligibilityContributor.gibbonINInterventionEligibilityContributorID',
                'gibbonINInterventionEligibilityContributor.gibbonINInterventionEligibilityAssessmentID',
                'gibbonINInterventionEligibilityContributor.gibbonPersonIDContributor',
                'gibbonINInterventionEligibilityContributor.gibbonINEligibilityAssessmentTypeID',
                'gibbonINInterventionEligibilityContributor.notes',
                'gibbonINInterventionEligibilityContributor.status',
                'gibbonINInterventionEligibilityContributor.contribution',
                'gibbonINInterventionEligibilityContributor.recommendation',
                'gibbonINInterventionEligibilityContributor.timestampCreated',
                'gibbonINInterventionEligibilityContributor.timestampModified',
                'gibbonPerson.title',
                'gibbonPerson.preferredName',
                'gibbonPerson.surname',
                'gibbonINEligibilityAssessmentType.name as assessmentTypeName'
            ])
            ->leftJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonINInterventionEligibilityContributor.gibbonPersonIDContributor')
            ->leftJoin('gibbonINEligibilityAssessmentType', 'gibbonINEligibilityAssessmentType.gibbonINEligibilityAssessmentTypeID=gibbonINInterventionEligibilityContributor.gibbonINEligibilityAssessmentTypeID');

        $criteria->addFilterRules([
            'gibbonINInterventionEligibilityAssessmentID' => function ($query, $gibbonINInterventionEligibilityAssessmentID) {
                return $query
                    ->where('gibbonINInterventionEligibilityContributor.gibbonINInterventionEligibilityAssessmentID = :gibbonINInterventionEligibilityAssessmentID')
                    ->bindValue('gibbonINInterventionEligibilityAssessmentID', $gibbonINInterventionEligibilityAssessmentID);
            },
            'status' => function ($query, $status) {
                return $query
                    ->where('gibbonINInterventionEligibilityContributor.status = :status')
                    ->bindValue('status', $status);
            },
            'gibbonPersonIDContributor' => function ($query, $gibbonPersonIDContributor) {
                return $query
                    ->where('gibbonINInterventionEligibilityContributor.gibbonPersonIDContributor = :gibbonPersonIDContributor')
                    ->bindValue('gibbonPersonIDContributor', $gibbonPersonIDContributor);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }
    
    /**
     * Scrub function for GDPR compliance.
     *
     * @param DeleteInterface $delete
     * @param string $gibbonPersonID
     * @return DeleteInterface
     */
    protected function scrubByPerson(DeleteInterface $delete, string $gibbonPersonID): DeleteInterface
    {
        return $delete->where('gibbonPersonIDContributor = :gibbonPersonID')->bindValue('gibbonPersonID', $gibbonPersonID);
    }
}
