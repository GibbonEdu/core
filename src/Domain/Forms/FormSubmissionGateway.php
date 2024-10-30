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

namespace Gibbon\Domain\Forms;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

class FormSubmissionGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonFormSubmission';
    private static $primaryKey = 'gibbonFormSubmissionID';
    private static $searchableColumns = ['gibbonFormSubmission.name'];

    public function queryFormsBySchoolYear(QueryCriteria $criteria, $gibbonSchoolYearID, $type = null)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->cols([
                'gibbonFormSubmission.gibbonFormSubmissionID',
                'gibbonFormSubmission.gibbonFormID',
                'gibbonFormSubmission.identifier',
                'gibbonFormSubmission.status',
                'gibbonFormSubmission.timestampCreated',
                'gibbonForm.gibbonFormID',
                'gibbonForm.name as formName',
                'gibbonAdmissionsAccount.gibbonAdmissionsAccountID',
                'gibbonAdmissionsAccount.email',
             ])
            ->from($this->getTableName())
            ->innerJoin('gibbonForm', 'gibbonFormSubmission.gibbonFormID=gibbonForm.gibbonFormID')
            ->innerJoin('gibbonSchoolYear', 'gibbonFormSubmission.timestampCreated BETWEEN gibbonSchoolYear.firstDay AND gibbonSchoolYear.lastDay')
            ->leftJoin('gibbonAdmissionsAccount', "gibbonFormSubmission.foreignTable='gibbonAdmissionsAccount' AND gibbonFormSubmission.foreignTableID=gibbonAdmissionsAccount.gibbonAdmissionsAccountID")
            ->where('gibbonSchoolYear.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where("gibbonForm.type<>'Application'");
        
            if (!empty($type)) {
                $query->where('gibbonForm.type=:type')
                      ->bindValue('type', $type);
            }

        return $this->runQuery($query, $criteria);
    }

    public function querySubmissionsByForm(QueryCriteria $criteria, $gibbonFormID)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols(['gibbonFormSubmission.gibbonFormSubmissionID', 'gibbonFormSubmission.gibbonFormID', 'gibbonFormSubmission.identifier'])
            ->where('gibbonFormSubmission.gibbonFormID=:gibbonFormID')
            ->bindValue('gibbonFormID', $gibbonFormID);

        return $this->runQuery($query, $criteria);
    }

    public function queryFormSubmissionsByContext(QueryCriteria $criteria, $foreignTable, $foreignTableID) 
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->cols([
                'gibbonFormSubmission.gibbonFormSubmissionID',
                'gibbonFormSubmission.gibbonFormID',
                'gibbonFormSubmission.identifier',
                'gibbonFormSubmission.status',
                'gibbonFormSubmission.timestampCreated',
                'gibbonForm.gibbonFormID',
                'gibbonForm.name as formName',
            ])
            ->from($this->getTableName())
            ->innerJoin('gibbonForm', 'gibbonFormSubmission.gibbonFormID=gibbonForm.gibbonFormID')
            ->where('gibbonFormSubmission.foreignTable=:foreignTable')
            ->bindValue('foreignTable', $foreignTable)
            ->where('gibbonFormSubmission.foreignTableID=:foreignTableID')
            ->bindValue('foreignTableID', $foreignTableID);

        return $this->runQuery($query, $criteria);
    }

    public function getFormSubmissionByIdentifier($gibbonFormID, $identifier, $fields = null)
    {
        return $this->selectBy(['gibbonFormID' => $gibbonFormID, 'identifier' => $identifier], $fields)->fetch();
    }

    public function getNewUniqueIdentifier(string $gibbonFormID)
    {
        $data = ['gibbonFormID' => $gibbonFormID];

        do {
            $data['identifier'] = bin2hex(random_bytes(20));
        } while (!$this->unique($data, ['gibbonFormID', 'identifier']));

        return $data['identifier'];
    }
}
