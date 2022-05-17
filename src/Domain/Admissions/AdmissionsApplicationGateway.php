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

namespace Gibbon\Domain\Admissions;

use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\Traits\TableAware;

/**
 * Admissions Application Forms
 *
 * @version v24
 * @since   v24
 */
class AdmissionsApplicationGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonAdmissionsApplication';
    private static $primaryKey = 'gibbonAdmissionsApplicationID';

    private static $searchableColumns = ['email'];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryApplicationsBySchoolYear(QueryCriteria $criteria, $gibbonSchoolYearID, $type = 'Application')
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->cols([
                'gibbonAdmissionsApplication.gibbonAdmissionsApplicationID',
                'gibbonAdmissionsApplication.gibbonFormID',
                'gibbonAdmissionsApplication.identifier',
                'gibbonAdmissionsApplication.status',
                'gibbonAdmissionsApplication.timestampCreated',
                'gibbonForm.gibbonFormID',
                'gibbonForm.name as formName',
                'gibbonAdmissionsAccount.gibbonAdmissionsAccountID',
                'gibbonAdmissionsAccount.email',
                'gibbonYearGroup.nameShort as yearGroup',
                'gibbonFormGroup.nameShort as formGroup',
                'gibbonAdmissionsApplication.data->>"$.surname" as studentSurname',
                'gibbonAdmissionsApplication.data->>"$.preferredName" as studentPreferredName',
             ])
            ->from($this->getTableName())
            ->innerJoin('gibbonForm', 'gibbonAdmissionsApplication.gibbonFormID=gibbonForm.gibbonFormID')
            ->leftJoin('gibbonSchoolYear', 'gibbonSchoolYear.gibbonSchoolYearID=gibbonAdmissionsApplication.gibbonSchoolYearID')
            ->leftJoin('gibbonYearGroup', 'gibbonYearGroup.gibbonYearGroupID=gibbonAdmissionsApplication.gibbonYearGroupID')
            ->leftJoin('gibbonFormGroup', 'gibbonFormGroup.gibbonFormGroupID=gibbonAdmissionsApplication.gibbonFormGroupID')
            ->leftJoin('gibbonAdmissionsAccount', "gibbonAdmissionsApplication.foreignTable='gibbonAdmissionsAccount' AND gibbonAdmissionsApplication.foreignTableID=gibbonAdmissionsAccount.gibbonAdmissionsAccountID")
            ->where('gibbonSchoolYear.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonForm.type=:type')
            ->bindValue('type', $type);

        $criteria->addFilterRules([
            'admissionsAccount' => function ($query, $admissionsAccount) {
                return $query
                    ->where('gibbonAdmissionsAccount.gibbonAdmissionsAccountID = :admissionsAccount')
                    ->bindValue('admissionsAccount', $admissionsAccount);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function queryApplicationsByForm(QueryCriteria $criteria, $gibbonFormID)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols(['gibbonAdmissionsApplication.gibbonAdmissionsApplicationID', 'gibbonAdmissionsApplication.gibbonFormID', 'gibbonAdmissionsApplication.identifier'])
            ->where('gibbonAdmissionsApplication.gibbonFormID=:gibbonFormID')
            ->bindValue('gibbonFormID', $gibbonFormID);

        return $this->runQuery($query, $criteria);
    }

    public function queryApplicationsByContext(QueryCriteria $criteria, $foreignTable, $foreignTableID) 
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->cols([
                'gibbonAdmissionsApplication.gibbonAdmissionsApplicationID',
                'gibbonAdmissionsApplication.gibbonFormID',
                'gibbonAdmissionsApplication.identifier',
                'gibbonAdmissionsApplication.status',
                'gibbonAdmissionsApplication.timestampCreated',
                'gibbonForm.gibbonFormID',
                'gibbonForm.name as formName',
            ])
            ->from($this->getTableName())
            ->innerJoin('gibbonForm', 'gibbonAdmissionsApplication.gibbonFormID=gibbonForm.gibbonFormID')
            ->where('gibbonAdmissionsApplication.foreignTable=:foreignTable')
            ->bindValue('foreignTable', $foreignTable)
            ->where('gibbonAdmissionsApplication.foreignTableID=:foreignTableID')
            ->bindValue('foreignTableID', $foreignTableID);

        return $this->runQuery($query, $criteria);
    }

    public function selectMostRecentApplicationByContext($gibbonFormID, $foreignTable, $foreignTableID) 
    {
        $query = $this
            ->newSelect()
            ->cols([
                'gibbonAdmissionsApplication.gibbonAdmissionsApplicationID',
                'gibbonAdmissionsApplication.data',
            ])
            ->from($this->getTableName())
            ->innerJoin('gibbonForm', 'gibbonAdmissionsApplication.gibbonFormID=gibbonForm.gibbonFormID')
            ->where('gibbonAdmissionsApplication.gibbonFormID=:gibbonFormID')
            ->bindValue('gibbonFormID', $gibbonFormID)
            ->where('gibbonAdmissionsApplication.foreignTable=:foreignTable')
            ->bindValue('foreignTable', $foreignTable)
            ->where('gibbonAdmissionsApplication.foreignTableID=:foreignTableID')
            ->bindValue('foreignTableID', $foreignTableID)
            ->orderBy(['gibbonAdmissionsApplication.timestampCreated DESC'])
            ->limit(1);

        return $this->runSelect($query);
    }

    public function getApplicationByIdentifier($gibbonFormID, $identifier)
    {
        return $this->selectBy(['gibbonFormID' => $gibbonFormID, 'identifier' => $identifier])->fetch();
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
