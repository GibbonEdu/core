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

    private static $searchableColumns = ['owner', 'gibbonAdmissionsApplicationID'];

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
                'gibbonAdmissionsApplication.priority',
                'gibbonAdmissionsApplication.milestones',
                'gibbonAdmissionsApplication.timestampCreated',
                'gibbonForm.gibbonFormID',
                'gibbonForm.name as formName',
                'gibbonAdmissionsAccount.gibbonAdmissionsAccountID',
                'gibbonAdmissionsAccount.email',
                'gibbonYearGroup.name as yearGroup',
                'gibbonFormGroup.name as formGroup',
                'gibbonAdmissionsApplication.data->>"$.surname" as studentSurname',
                'gibbonAdmissionsApplication.data->>"$.preferredName" as studentPreferredName',
                'gibbonAdmissionsApplication.data->>"$.schoolName1" as schoolName1',
                'gibbonAdmissionsApplication.data->>"$.schoolName2" as schoolName2',
                'gibbonAdmissionsApplication.data->>"$.schoolDate1" as schoolDate1',
                'gibbonAdmissionsApplication.data->>"$.schoolDate2" as schoolDate2',
                'gibbonAdmissionsApplication.data->>"$.dob" as dob',
             ])
            ->from($this->getTableName())
            ->innerJoin('gibbonForm', 'gibbonAdmissionsApplication.gibbonFormID=gibbonForm.gibbonFormID')
            ->leftJoin('gibbonSchoolYear', 'gibbonSchoolYear.gibbonSchoolYearID=gibbonAdmissionsApplication.gibbonSchoolYearID')
            ->leftJoin('gibbonSchoolYear as schoolYearCheck', 'gibbonAdmissionsApplication.timestampCreated BETWEEN schoolYearCheck.firstDay AND schoolYearCheck.lastDay')
            ->leftJoin('gibbonYearGroup', 'gibbonYearGroup.gibbonYearGroupID=gibbonAdmissionsApplication.gibbonYearGroupID')
            ->leftJoin('gibbonFormGroup', 'gibbonFormGroup.gibbonFormGroupID=gibbonAdmissionsApplication.gibbonFormGroupID')
            ->leftJoin('gibbonAdmissionsAccount', "gibbonAdmissionsApplication.foreignTable='gibbonAdmissionsAccount' AND gibbonAdmissionsApplication.foreignTableID=gibbonAdmissionsAccount.gibbonAdmissionsAccountID")
            ->where('((gibbonSchoolYear.gibbonSchoolYearID IS NOT NULL AND gibbonSchoolYear.gibbonSchoolYearID=:gibbonSchoolYearID) OR (gibbonSchoolYear.gibbonSchoolYearID IS NULL AND schoolYearCheck.gibbonSchoolYearID=:gibbonSchoolYearID))')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonForm.type=:type')
            ->bindValue('type', $type);

        $criteria->addFilterRules([
            'admissionsAccount' => function ($query, $admissionsAccount) {
                return $query
                    ->where('gibbonAdmissionsAccount.gibbonAdmissionsAccountID = :admissionsAccount')
                    ->bindValue('admissionsAccount', $admissionsAccount);
            },
            'status' => function ($query, $status) {
                return $query
                    ->where('gibbonAdmissionsApplication.status = :status')
                    ->bindValue('status', ucwords($status));
            },
            'paid' => function ($query, $paymentMade) {
                return $query
                    ->where(strtoupper($paymentMade) == 'Y'
                    ? 'gibbonAdmissionsApplication.gibbonPaymentIDSubmit IS NOT NULL'
                    : 'gibbonAdmissionsApplication.gibbonPaymentIDSubmit IS NULL');
            },
            'formGroup' => function ($query, $value) {
                return $query
                    ->where(strtoupper($value) == 'Y'
                        ? 'gibbonAdmissionsApplication.gibbonFormGroupID IS NOT NULL'
                        : 'gibbonAdmissionsApplication.gibbonFormGroupID IS NULL');
            },
            'yearGroup' => function ($query, $gibbonYearGroupID) {
                return $query
                    ->where('gibbonAdmissionsApplication.gibbonYearGroupID = :gibbonYearGroupID')
                    ->bindValue('gibbonYearGroupID', $gibbonYearGroupID);
            },
            'incomplete' => function ($query, $incomplete) {
                return $incomplete != 'N' ? $query : $query
                    ->where("gibbonAdmissionsApplication.status <> 'Incomplete'");
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
                'gibbonAdmissionsApplication.gibbonPaymentIDSubmit',
                'gibbonAdmissionsApplication.gibbonPaymentIDProcess',
                'gibbonForm.gibbonFormID',
                'gibbonForm.name as formName',
                'gibbonFormPage.sequenceNumber as page',
                'gibbonAdmissionsApplication.data->>"$.surname" as studentSurname',
                'gibbonAdmissionsApplication.data->>"$.preferredName" as studentPreferredName',
                'gibbonAdmissionsApplication.data->>"$.PaySubmissionFeeComplete" AS submissionFeeComplete',
                'gibbonAdmissionsApplication.data->>"$.PayProcessingFeeComplete" AS processingFeeComplete',
                'gibbonForm.config->>"$.formSubmissionFee" as formSubmissionFee',
                'gibbonForm.config->>"$.formProcessingFee" as formProcessingFee',
            ])
            ->from($this->getTableName())
            ->innerJoin('gibbonForm', 'gibbonAdmissionsApplication.gibbonFormID=gibbonForm.gibbonFormID')
            ->leftJoin('gibbonFormPage', 'gibbonAdmissionsApplication.gibbonFormPageID=gibbonFormPage.gibbonFormPageID')
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
            ->where("status <> 'Incomplete'")
            ->limit(1);

        return $this->runSelect($query);
    }

    public function getApplicationDetailsByID($gibbonAdmissionsApplicationID)
    {
        $data = ['gibbonAdmissionsApplicationID' => $gibbonAdmissionsApplicationID];
        $sql = "SELECT gibbonAdmissionsApplication.*, 
                    gibbonForm.name as applicationName,
                    gibbonAdmissionsApplication.data->>'$.surname' AS studentSurname,
                    gibbonAdmissionsApplication.data->>'$.preferredName' AS studentPreferredName,
                    gibbonAdmissionsApplication.data->>'$.PaySubmissionFeeComplete' AS submissionFeeComplete,
                    gibbonAdmissionsApplication.data->>'$.PayProcessingFeeComplete' AS processingFeeComplete
                FROM gibbonAdmissionsApplication
                JOIN gibbonForm ON (gibbonAdmissionsApplication.gibbonFormID=gibbonForm.gibbonFormID)
                WHERE gibbonAdmissionsApplication.gibbonAdmissionsApplicationID=:gibbonAdmissionsApplicationID";

        return $this->db()->selectOne($sql, $data);
    }

    public function getApplicationByIdentifier($gibbonFormID, $identifier, $foreignTable, $foreignTableID, $fields = null)
    {
        return $this->selectBy(['gibbonFormID' => $gibbonFormID, 'identifier' => $identifier, 'foreignTable' => $foreignTable, 'foreignTableID' => $foreignTableID], $fields)->fetch();
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
