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
 * Intervention Contributors Gateway
 *
 * @version v29
 * @since   v29
 */
class INInterventionContributorGateway extends QueryableGateway implements ScrubbableGateway
{
    use TableAware;
    use Scrubbable;
    use ScrubByPerson;

    private static $tableName = 'gibbonINInterventionContributor';
    private static $primaryKey = 'gibbonINInterventionContributorID';

    private static $searchableColumns = [];

    private static $scrubbableKey = 'gibbonPersonID';
    private static $scrubbableColumns = [];

    /**
     * @param QueryCriteria $criteria
     * @param int $gibbonINInterventionID
     * @return DataSet
     */
    public function queryContributorsByIntervention(QueryCriteria $criteria, $gibbonINInterventionID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonINInterventionContributor.*',
                'person.title',
                'person.surname',
                'person.preferredName'
            ])
            ->innerJoin('gibbonPerson AS person', 'gibbonINInterventionContributor.gibbonPersonID=person.gibbonPersonID')
            ->where('gibbonINInterventionContributor.gibbonINInterventionID=:gibbonINInterventionID')
            ->bindValue('gibbonINInterventionID', $gibbonINInterventionID)
            ->orderBy(['gibbonINInterventionContributor.dateCreated DESC']);

        return $this->runQuery($query, $criteria);
    }

    /**
     * @param QueryCriteria $criteria
     * @param int $gibbonPersonID
     * @return DataSet
     */
    public function queryContributorsByPerson(QueryCriteria $criteria, $gibbonPersonID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonINInterventionContributor.*',
                'gibbonINIntervention.name as interventionName',
                'student.surname',
                'student.preferredName',
                'gibbonFormGroup.nameShort as formGroup'
            ])
            ->innerJoin('gibbonINIntervention', 'gibbonINInterventionContributor.gibbonINInterventionID=gibbonINIntervention.gibbonINInterventionID')
            ->innerJoin('gibbonINInvestigation', 'gibbonINIntervention.gibbonINInvestigationID=gibbonINInvestigation.gibbonINInvestigationID')
            ->innerJoin('gibbonPerson AS student', 'gibbonINInvestigation.gibbonPersonIDStudent=student.gibbonPersonID')
            ->innerJoin('gibbonStudentEnrolment', 'student.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonFormGroup', 'gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID')
            ->where('gibbonINInterventionContributor.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->where('gibbonStudentEnrolment.gibbonSchoolYearID=gibbonINInvestigation.gibbonSchoolYearID')
            ->orderBy(['gibbonINInterventionContributor.status', 'gibbonINInterventionContributor.dateCreated DESC']);

        return $this->runQuery($query, $criteria);
    }

    /**
     * Selects all contributors for a given intervention
     *
     * @param int $gibbonINInterventionID
     * @return Result
     */
    public function selectContributorsByIntervention($gibbonINInterventionID)
    {
        $sql = "SELECT gibbonINInterventionContributor.*, person.title, person.surname, person.preferredName, person.gibbonPersonID
                FROM gibbonINInterventionContributor
                JOIN gibbonPerson AS person ON (gibbonINInterventionContributor.gibbonPersonID=person.gibbonPersonID)
                WHERE gibbonINInterventionContributor.gibbonINInterventionID=:gibbonINInterventionID
                ORDER BY gibbonINInterventionContributor.dateCreated DESC";

        return $this->db()->select($sql, ['gibbonINInterventionID' => $gibbonINInterventionID]);
    }

    public function getContributorByID($gibbonINInterventionContributorID)
    {
        $query = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols([
                'gibbonINInterventionContributor.*',
                'person.title',
                'person.surname',
                'person.preferredName'
            ])
            ->innerJoin('gibbonPerson AS person', 'gibbonINInterventionContributor.gibbonPersonID=person.gibbonPersonID')
            ->where('gibbonINInterventionContributor.gibbonINInterventionContributorID=:gibbonINInterventionContributorID')
            ->bindValue('gibbonINInterventionContributorID', $gibbonINInterventionContributorID);

        return $this->runSelect($query)->fetch();
    }
}
