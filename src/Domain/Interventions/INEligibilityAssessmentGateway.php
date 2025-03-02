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
 * Eligibility Assessment Gateway
 *
 * @version v29
 * @since   v29
 */
class INEligibilityAssessmentGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonINEligibilityAssessment';
    private static $primaryKey = 'gibbonINEligibilityAssessmentID';

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryAssessmentsByReferral(QueryCriteria $criteria, $gibbonINReferralID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonINEligibilityAssessment.gibbonINEligibilityAssessmentID', 
                'gibbonINEligibilityAssessment.gibbonINReferralID', 
                'gibbonINEligibilityAssessment.gibbonPersonIDContributor', 
                'gibbonINEligibilityAssessment.type', 
                'gibbonINEligibilityAssessment.assessment', 
                'gibbonINEligibilityAssessment.recommendation', 
                'gibbonINEligibilityAssessment.dateCompleted', 
                'gibbonINEligibilityAssessment.timestampCreated',
                'contributor.title', 
                'contributor.surname', 
                'contributor.preferredName'
            ])
            ->innerJoin('gibbonPerson AS contributor', 'contributor.gibbonPersonID=gibbonINEligibilityAssessment.gibbonPersonIDContributor')
            ->where('gibbonINEligibilityAssessment.gibbonINReferralID = :gibbonINReferralID')
            ->bindValue('gibbonINReferralID', $gibbonINReferralID);

        return $this->runQuery($query, $criteria);
    }

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryAssessmentsByContributor(QueryCriteria $criteria, $gibbonPersonID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonINEligibilityAssessment.gibbonINEligibilityAssessmentID', 
                'gibbonINEligibilityAssessment.gibbonINReferralID', 
                'gibbonINEligibilityAssessment.gibbonPersonIDContributor', 
                'gibbonINEligibilityAssessment.type', 
                'gibbonINEligibilityAssessment.assessment', 
                'gibbonINEligibilityAssessment.recommendation', 
                'gibbonINEligibilityAssessment.dateCompleted', 
                'gibbonINEligibilityAssessment.timestampCreated',
                'gibbonINReferral.name as referralName',
                'student.surname as studentSurname',
                'student.preferredName as studentPreferredName'
            ])
            ->innerJoin('gibbonINReferral', 'gibbonINReferral.gibbonINReferralID=gibbonINEligibilityAssessment.gibbonINReferralID')
            ->innerJoin('gibbonPerson AS student', 'student.gibbonPersonID=gibbonINReferral.gibbonPersonIDStudent')
            ->where('gibbonINEligibilityAssessment.gibbonPersonIDContributor = :gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID);

        return $this->runQuery($query, $criteria);
    }

    /**
     * Get assessments by referral ID
     *
     * @param int $gibbonINReferralID
     * @return array
     */
    public function selectAssessmentsByReferral($gibbonINReferralID)
    {
        $query = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols([
                'gibbonINEligibilityAssessment.gibbonINEligibilityAssessmentID', 
                'gibbonINEligibilityAssessment.gibbonINReferralID', 
                'gibbonINEligibilityAssessment.gibbonPersonIDContributor', 
                'gibbonINEligibilityAssessment.type', 
                'gibbonINEligibilityAssessment.assessment', 
                'gibbonINEligibilityAssessment.recommendation', 
                'gibbonINEligibilityAssessment.dateCompleted', 
                'gibbonINEligibilityAssessment.timestampCreated',
                'contributor.title', 
                'contributor.surname', 
                'contributor.preferredName',
                "CONCAT(contributor.title, ' ', contributor.preferredName, ' ', contributor.surname) as contributorName"
            ])
            ->innerJoin('gibbonPerson AS contributor', 'contributor.gibbonPersonID=gibbonINEligibilityAssessment.gibbonPersonIDContributor')
            ->where('gibbonINEligibilityAssessment.gibbonINReferralID = :gibbonINReferralID')
            ->bindValue('gibbonINReferralID', $gibbonINReferralID);

        return $this->runSelect($query);
    }
}
