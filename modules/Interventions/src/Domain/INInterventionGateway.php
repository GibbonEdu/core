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
use Aura\SqlQuery\Common\SelectInterface;

/**
 * Intervention Gateway
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
    private static $searchableColumns = ['name', 'description'];
    
    private static $scrubbableKey = 'gibbonPersonIDCreator';
    private static $scrubbableColumns = ['name' => '', 'description' => '', 'formTutorNotes' => '', 'outcomeNotes' => ''];

    private $session;

    public function __construct(\Gibbon\Contracts\Database\Connection $db, $session = null)
    {
        parent::__construct($db);
        $this->session = $session;
    }

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryInterventions(QueryCriteria $criteria, $gibbonSchoolYearID = null, $gibbonPersonIDCreator = null)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonINIntervention.gibbonINInterventionID',
                'gibbonINIntervention.name',
                'gibbonINIntervention.description',
                'gibbonINIntervention.status',
                'gibbonINIntervention.formTutorDecision',
                'gibbonINIntervention.formTutorNotes',
                'gibbonINIntervention.outcomeNotes',
                'gibbonINIntervention.outcomeDecision',
                'gibbonINIntervention.timestampCreated',
                'gibbonINIntervention.gibbonPersonIDCreator',
                'student.gibbonPersonID',
                'student.surname',
                'student.preferredName',
                'formGroup.name AS formGroup',
                'formGroup.gibbonFormGroupID',
                'creator.title AS titleCreator',
                'creator.surname AS surnameCreator',
                'creator.preferredName AS preferredNameCreator',
                'formTutor.title AS titleFormTutor',
                'formTutor.surname AS surnameFormTutor',
                'formTutor.preferredName AS preferredNameFormTutor',
                "(SELECT MAX(gibbonINInterventionStrategy.targetDate) FROM gibbonINInterventionStrategy WHERE gibbonINInterventionStrategy.gibbonINInterventionID=gibbonINIntervention.gibbonINInterventionID) as targetDate",
                "(CASE WHEN gibbonINIntervention.description LIKE '%PARENTS INFORMED: Yes%' THEN 'Y' ELSE 'N' END) as parentConsent"
            ])
            ->innerJoin('gibbonPerson AS student', 'student.gibbonPersonID=gibbonINIntervention.gibbonPersonIDStudent')
            ->leftJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonPersonID=student.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->leftJoin('gibbonFormGroup AS formGroup', 'formGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID')
            ->leftJoin('gibbonPerson AS creator', 'creator.gibbonPersonID=gibbonINIntervention.gibbonPersonIDCreator')
            ->leftJoin('gibbonPerson AS formTutor', 'formTutor.gibbonPersonID=gibbonINIntervention.gibbonPersonIDFormTutor')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID ?? ($this->session ? $this->session->get('gibbonSchoolYearID') : ''));

        if (!empty($gibbonPersonIDCreator)) {
            $query->where('gibbonINIntervention.gibbonPersonIDCreator = :gibbonPersonIDCreator')
                  ->bindValue('gibbonPersonIDCreator', $gibbonPersonIDCreator);
        }

        $criteria->addFilterRules([
            'status' => function ($query, $status) {
                return $query
                    ->where('gibbonINIntervention.status = :status')
                    ->bindValue('status', $status);
            },
            'student' => function ($query, $gibbonPersonID) {
                return $query
                    ->where('student.gibbonPersonID = :gibbonPersonID')
                    ->bindValue('gibbonPersonID', $gibbonPersonID);
            },
            'formGroup' => function ($query, $gibbonFormGroupID) {
                return $query
                    ->where('formGroup.gibbonFormGroupID = :gibbonFormGroupID')
                    ->bindValue('gibbonFormGroupID', $gibbonFormGroupID);
            },
            'yearGroup' => function ($query, $gibbonYearGroupID) {
                return $query
                    ->where('gibbonStudentEnrolment.gibbonYearGroupID = :gibbonYearGroupID')
                    ->bindValue('gibbonYearGroupID', $gibbonYearGroupID);
            }
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function queryInterventionsByCreator(QueryCriteria $criteria, $gibbonPersonID)
    {
        $query = $this->queryInterventions($criteria);
        $query->where('gibbonINIntervention.gibbonPersonIDCreator = :gibbonPersonIDCreator')
              ->bindValue('gibbonPersonIDCreator', $gibbonPersonID);

        return $this->runQuery($query, $criteria);
    }

    public function getInterventionByID($gibbonINInterventionID)
    {
        $query = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols([
                'gibbonINIntervention.*',
                'student.surname',
                'student.preferredName',
                'formGroup.name as formGroup',
                'yearGroup.name as yearGroup',
                'creator.title',
                'creator.surname as creatorSurname',
                'creator.preferredName as creatorPreferredName'
            ])
            ->innerJoin('gibbonPerson AS student', 'student.gibbonPersonID=gibbonINIntervention.gibbonPersonIDStudent')
            ->innerJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonPersonID=student.gibbonPersonID')
            ->innerJoin('gibbonFormGroup AS formGroup', 'formGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID')
            ->innerJoin('gibbonYearGroup AS yearGroup', 'yearGroup.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID')
            ->innerJoin('gibbonPerson AS creator', 'creator.gibbonPersonID=gibbonINIntervention.gibbonPersonIDCreator')
            ->where('gibbonINIntervention.gibbonINInterventionID = :gibbonINInterventionID')
            ->bindValue('gibbonINInterventionID', $gibbonINInterventionID);

        return $this->runSelect($query)->fetch();
    }
    
    protected function runUpdate(UpdateInterface $query) : bool
    {
        return $this->db()->update($query->getStatement(), $query->getBindValues());
    }

    protected function runDelete(DeleteInterface $query) : bool
    {
        return $this->db()->delete($query->getStatement(), $query->getBindValues());
    }
}
