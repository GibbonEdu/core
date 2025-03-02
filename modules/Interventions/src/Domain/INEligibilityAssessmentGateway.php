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

/**
 * Eligibility Assessment Gateway
 *
 * @version v29
 * @since   v29
 */
class INEligibilityAssessmentGateway extends QueryableGateway implements ScrubbableGateway
{
    use TableAware;
    use Scrubbable;
    use ScrubByPerson;

    private static $tableName = 'gibbonINEligibilityAssessment';
    private static $primaryKey = 'gibbonINEligibilityAssessmentID';

    private static $searchableColumns = [];

    private static $scrubbableKey = 'gibbonPersonIDAssessor';
    private static $scrubbableColumns = ['notes' => null];

    /**
     * @param QueryCriteria $criteria
     * @param int $gibbonINReferralID
     * @return DataSet
     */
    public function queryAssessmentsByReferral(QueryCriteria $criteria, $gibbonINReferralID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonINEligibilityAssessment.*',
                'gibbonINEligibilityAssessmentType.name as assessmentName',
                'gibbonINEligibilityAssessmentType.description as assessmentDescription',
                'assessor.title',
                'assessor.surname',
                'assessor.preferredName'
            ])
            ->innerJoin('gibbonINEligibilityAssessmentType', 'gibbonINEligibilityAssessment.gibbonINEligibilityAssessmentTypeID=gibbonINEligibilityAssessmentType.gibbonINEligibilityAssessmentTypeID')
            ->leftJoin('gibbonPerson AS assessor', 'gibbonINEligibilityAssessment.gibbonPersonIDAssessor=assessor.gibbonPersonID')
            ->where('gibbonINEligibilityAssessment.gibbonINReferralID=:gibbonINReferralID')
            ->bindValue('gibbonINReferralID', $gibbonINReferralID);

        return $this->runQuery($query, $criteria);
    }

    /**
     * @param QueryCriteria $criteria
     * @param int $gibbonPersonID
     * @return DataSet
     */
    public function queryAssessmentsByAssessor(QueryCriteria $criteria, $gibbonPersonID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonINEligibilityAssessment.*',
                'gibbonINEligibilityAssessmentType.name as assessmentName',
                'gibbonINReferral.gibbonPersonIDStudent',
                'student.surname',
                'student.preferredName',
                'gibbonFormGroup.nameShort as formGroup'
            ])
            ->innerJoin('gibbonINEligibilityAssessmentType', 'gibbonINEligibilityAssessment.gibbonINEligibilityAssessmentTypeID=gibbonINEligibilityAssessmentType.gibbonINEligibilityAssessmentTypeID')
            ->innerJoin('gibbonINReferral', 'gibbonINEligibilityAssessment.gibbonINReferralID=gibbonINReferral.gibbonINReferralID')
            ->innerJoin('gibbonPerson AS student', 'gibbonINReferral.gibbonPersonIDStudent=student.gibbonPersonID')
            ->innerJoin('gibbonStudentEnrolment', 'student.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonFormGroup', 'gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID')
            ->where('gibbonINEligibilityAssessment.gibbonPersonIDAssessor=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID);

        return $this->runQuery($query, $criteria);
    }
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryPendingAssessments(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonINEligibilityAssessment.*',
                'gibbonINEligibilityAssessmentType.name as assessmentName',
                'gibbonINReferral.gibbonPersonIDStudent',
                'student.surname',
                'student.preferredName',
                'gibbonFormGroup.nameShort as formGroup'
            ])
            ->innerJoin('gibbonINEligibilityAssessmentType', 'gibbonINEligibilityAssessment.gibbonINEligibilityAssessmentTypeID=gibbonINEligibilityAssessmentType.gibbonINEligibilityAssessmentTypeID')
            ->innerJoin('gibbonINReferral', 'gibbonINEligibilityAssessment.gibbonINReferralID=gibbonINReferral.gibbonINReferralID')
            ->innerJoin('gibbonPerson AS student', 'gibbonINReferral.gibbonPersonIDStudent=student.gibbonPersonID')
            ->innerJoin('gibbonStudentEnrolment', 'student.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonFormGroup', 'gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID')
            ->where('gibbonINEligibilityAssessment.result = :result')
            ->bindValue('result', 'Inconclusive');

        return $this->runQuery($query, $criteria);
    }
}
