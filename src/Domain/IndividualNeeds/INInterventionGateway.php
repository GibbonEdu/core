<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

namespace Gibbon\Domain\IndividualNeeds;

use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\ScrubbableGateway;
use Gibbon\Domain\Traits\Scrubbable;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\Traits\ScrubByPerson;

/**
 * Interventions Gateway
 *
 * @version v29
 * @since   v29
 */
class INInterventionGateway extends QueryableGateway implements ScrubbableGateway
{
    use TableAware;
    use Scrubbable;
    use ScrubByPerson;

    private static $tableName = 'gibbonINIntervention';
    private static $primaryKey = 'gibbonINInterventionID';

    private static $searchableColumns = [];

    private static $scrubbableKey = 'gibbonPersonIDCreator';
    private static $scrubbableColumns = ['name' => '', 'description' => '', 'strategies' => '', 'consentNotes' => null];

    /**
     * @param QueryCriteria $criteria
     * @param int $gibbonSchoolYearID
     * @param int $gibbonPersonIDCreator
     * @return DataSet
     */
    public function queryInterventions(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonPersonIDCreator = null)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonINIntervention.*',
                'gibbonINInvestigation.gibbonPersonIDStudent',
                'student.gibbonPersonID',
                'student.surname',
                'student.preferredName',
                'gibbonFormGroup.nameShort AS formGroup',
                'creator.title AS titleCreator',
                'creator.surname AS surnameCreator',
                'creator.preferredName AS preferredNameCreator'
            ])
            ->innerJoin('gibbonINInvestigation', 'gibbonINIntervention.gibbonINInvestigationID=gibbonINInvestigation.gibbonINInvestigationID')
            ->innerJoin('gibbonPerson AS student', 'gibbonINInvestigation.gibbonPersonIDStudent=student.gibbonPersonID')
            ->innerJoin('gibbonStudentEnrolment', 'student.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonFormGroup', 'gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID')
            ->leftJoin('gibbonPerson AS creator', 'gibbonINIntervention.gibbonPersonIDCreator=creator.gibbonPersonID')
            ->where('gibbonINInvestigation.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonStudentEnrolment.gibbonSchoolYearID=gibbonINInvestigation.gibbonSchoolYearID');

        if (!empty($gibbonPersonIDCreator)) {
            $query->where('gibbonINIntervention.gibbonPersonIDCreator=:gibbonPersonIDCreator OR gibbonFormGroup.gibbonPersonIDTutor=:gibbonPersonIDCreator OR gibbonFormGroup.gibbonPersonIDTutor2=:gibbonPersonIDCreator OR gibbonFormGroup.gibbonPersonIDTutor3=:gibbonPersonIDCreator')
                ->bindValue('gibbonPersonIDCreator', $gibbonPersonIDCreator);
        }

        $criteria->addFilterRules([
            'student' => function ($query, $gibbonPersonID) {
                return $query
                    ->where('gibbonINInvestigation.gibbonPersonIDStudent=:gibbonPersonID')
                    ->bindValue('gibbonPersonID', $gibbonPersonID);
            },
            'formGroup' => function ($query, $gibbonFormGroupID) {
                return $query
                    ->where('gibbonStudentEnrolment.gibbonFormGroupID=:gibbonFormGroupID')
                    ->bindValue('gibbonFormGroupID', $gibbonFormGroupID);
            },
            'yearGroup' => function ($query, $gibbonYearGroupID) {
                return $query
                    ->where('gibbonStudentEnrolment.gibbonYearGroupID=:gibbonYearGroupID')
                    ->bindValue('gibbonYearGroupID', $gibbonYearGroupID);
            },
            'status' => function ($query, $status) {
                return $query
                    ->where('gibbonINIntervention.status=:status')
                    ->bindValue('status', $status);
            },
            'parentConsent' => function ($query, $parentConsent) {
                return $query
                    ->where('gibbonINIntervention.parentConsent=:parentConsent')
                    ->bindValue('parentConsent', $parentConsent);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function getInterventionByID($gibbonINInterventionID)
    {
        $query = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols([
                'gibbonINIntervention.*',
                'gibbonINInvestigation.gibbonPersonIDStudent',
                'student.gibbonPersonID',
                'student.surname',
                'student.preferredName',
                'gibbonFormGroup.nameShort AS formGroup',
                'gibbonFormGroup.gibbonPersonIDTutor',
                'gibbonFormGroup.gibbonPersonIDTutor2',
                'gibbonFormGroup.gibbonPersonIDTutor3',
                'creator.title AS titleCreator',
                'creator.surname AS surnameCreator',
                'creator.preferredName AS preferredNameCreator'
            ])
            ->innerJoin('gibbonINInvestigation', 'gibbonINIntervention.gibbonINInvestigationID=gibbonINInvestigation.gibbonINInvestigationID')
            ->innerJoin('gibbonPerson AS student', 'gibbonINInvestigation.gibbonPersonIDStudent=student.gibbonPersonID')
            ->innerJoin('gibbonStudentEnrolment', 'student.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonFormGroup', 'gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID')
            ->leftJoin('gibbonPerson AS creator', 'gibbonINIntervention.gibbonPersonIDCreator=creator.gibbonPersonID')
            ->where('gibbonINIntervention.gibbonINInterventionID=:gibbonINInterventionID')
            ->bindValue('gibbonINInterventionID', $gibbonINInterventionID)
            ->where('gibbonStudentEnrolment.gibbonSchoolYearID=gibbonINInvestigation.gibbonSchoolYearID');

        return $this->runSelect($query)->fetch();
    }

    public function getInterventionsByInvestigationID($gibbonINInvestigationID)
    {
        $query = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols([
                'gibbonINIntervention.*',
                'creator.title AS titleCreator',
                'creator.surname AS surnameCreator',
                'creator.preferredName AS preferredNameCreator'
            ])
            ->leftJoin('gibbonPerson AS creator', 'gibbonINIntervention.gibbonPersonIDCreator=creator.gibbonPersonID')
            ->where('gibbonINIntervention.gibbonINInvestigationID=:gibbonINInvestigationID')
            ->bindValue('gibbonINInvestigationID', $gibbonINInvestigationID)
            ->orderBy(['gibbonINIntervention.dateCreated DESC']);

        return $this->runSelect($query)->fetchAll();
    }
}
