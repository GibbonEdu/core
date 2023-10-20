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
                'gibbonAdmissionsAccount.accessID',
                'gibbonAdmissionsAccount.email',
                'gibbonYearGroup.name as yearGroup',
                'gibbonFormGroup.name as formGroup',
                'gibbonAdmissionsApplication.data',
                'JSON_UNQUOTE(JSON_EXTRACT(gibbonAdmissionsApplication.data, "$.surname")) as studentSurname',
                'JSON_UNQUOTE(JSON_EXTRACT(gibbonAdmissionsApplication.data, "$.preferredName")) as studentPreferredName',
                'JSON_UNQUOTE(JSON_EXTRACT(gibbonAdmissionsApplication.data, "$.schoolName1")) as schoolName1',
                'JSON_UNQUOTE(JSON_EXTRACT(gibbonAdmissionsApplication.data, "$.dob")) as dob',
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
                'JSON_UNQUOTE(JSON_EXTRACT(gibbonAdmissionsApplication.data, "$.surname")) as studentSurname',
                'JSON_UNQUOTE(JSON_EXTRACT(gibbonAdmissionsApplication.data, "$.preferredName")) as studentPreferredName',
                'JSON_UNQUOTE(JSON_EXTRACT(gibbonAdmissionsApplication.data, "$.PaySubmissionFeeComplete")) AS submissionFeeComplete',
                'JSON_UNQUOTE(JSON_EXTRACT(gibbonAdmissionsApplication.data, "$.PayProcessingFeeComplete")) AS processingFeeComplete',
                'JSON_UNQUOTE(JSON_EXTRACT(gibbonForm.config, "$.formSubmissionFee")) as formSubmissionFee',
                'JSON_UNQUOTE(JSON_EXTRACT(gibbonForm.config, "$.formProcessingFee")) as formProcessingFee',
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

    public function queryFamilyByApplication(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonAdmissionsApplicationID) 
    {
        // Application parents, post-acceptance or pre-existing
        $query = $this
            ->newQuery()
            ->distinct()
            ->cols([
                'gibbonAdmissionsApplication.gibbonAdmissionsApplicationID',
                'gibbonPerson.surname',
                'gibbonPerson.preferredName',
                'gibbonPerson.email',
                'gibbonPerson.gibbonPersonID',
                'gibbonRole.category as roleCategory',
                'gibbonPerson.status',
                'gibbonPerson.image_240',
                'gibbonFamilyRelationship.relationship',
                '"" as yearGroup',
                '"" as applicationID',
            ])
            ->from($this->getTableName())
            ->innerJoin('gibbonAdmissionsAccount', 'gibbonAdmissionsApplication.foreignTableID=gibbonAdmissionsAccount.gibbonAdmissionsAccountID')
            ->leftJoin('gibbonFamilyAdult', 'gibbonFamilyAdult.gibbonFamilyID=gibbonAdmissionsAccount.gibbonFamilyID')
            ->leftJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonFamilyAdult.gibbonPersonID OR gibbonPerson.gibbonPersonID=gibbonAdmissionsAccount.gibbonPersonID')
            ->leftJoin('gibbonRole', 'gibbonRole.gibbonRoleID=gibbonPerson.gibbonRoleIDPrimary')
            ->leftJoin('gibbonFamilyRelationship', 'gibbonFamilyRelationship.gibbonFamilyID=gibbonAdmissionsAccount.gibbonFamilyID AND gibbonFamilyRelationship.gibbonPersonID1=gibbonPerson.gibbonPersonID AND gibbonFamilyRelationship.gibbonPersonID2=JSON_UNQUOTE(JSON_EXTRACT(gibbonAdmissionsApplication.result, "$.gibbonPersonIDStudent"))')
            ->where('gibbonAdmissionsApplication.foreignTable="gibbonAdmissionsAccount"')
            ->where('gibbonAdmissionsApplication.gibbonAdmissionsApplicationID=:gibbonAdmissionsApplicationID')
            ->where('gibbonPerson.gibbonPersonID IS NOT NULL')
            ->bindValue('gibbonAdmissionsApplicationID', $gibbonAdmissionsApplicationID);

        // Application parents, existing family not attached to account, post-acceptance or pre-existing
        $this->unionAllWithCriteria($query, $criteria)
            ->cols([
                'gibbonAdmissionsApplication.gibbonAdmissionsApplicationID',
                'gibbonPerson.surname',
                'gibbonPerson.preferredName',
                'gibbonPerson.email',
                'gibbonPerson.gibbonPersonID',
                'gibbonRole.category as roleCategory',
                'gibbonPerson.status',
                'gibbonPerson.image_240',
                'gibbonFamilyRelationship.relationship',
                '"" as yearGroup',
                '"" as applicationID',
            ])
            ->from($this->getTableName())
            ->innerJoin('gibbonAdmissionsAccount', 'gibbonAdmissionsApplication.foreignTableID=gibbonAdmissionsAccount.gibbonAdmissionsAccountID')
            ->leftJoin('gibbonFamilyAdult', 'gibbonFamilyAdult.gibbonFamilyID=JSON_UNQUOTE(JSON_EXTRACT(gibbonAdmissionsApplication.data, "$.gibbonFamilyID"))')
            ->leftJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonFamilyAdult.gibbonPersonID')
            ->leftJoin('gibbonRole', 'gibbonRole.gibbonRoleID=gibbonPerson.gibbonRoleIDPrimary')
            ->leftJoin('gibbonFamilyRelationship', 'gibbonFamilyRelationship.gibbonFamilyID=gibbonAdmissionsAccount.gibbonFamilyID AND gibbonFamilyRelationship.gibbonPersonID1=gibbonPerson.gibbonPersonID AND gibbonFamilyRelationship.gibbonPersonID2=JSON_UNQUOTE(JSON_EXTRACT(gibbonAdmissionsApplication.result, "$.gibbonPersonIDStudent"))')
            ->where('gibbonAdmissionsApplication.foreignTable="gibbonAdmissionsAccount"')
            ->where('gibbonAdmissionsApplication.gibbonAdmissionsApplicationID=:gibbonAdmissionsApplicationID')
            ->where('gibbonPerson.gibbonPersonID IS NOT NULL')
            ->where('gibbonAdmissionsAccount.gibbonFamilyID IS NULL')
            ->bindValue('gibbonAdmissionsApplicationID', $gibbonAdmissionsApplicationID);

        // Application Parent 1, pre-acceptance
        $this->unionAllWithCriteria($query, $criteria)
            ->cols([
                'gibbonAdmissionsApplication.gibbonAdmissionsApplicationID',
                'JSON_UNQUOTE(JSON_EXTRACT(gibbonAdmissionsApplication.data, "$.parent1surname")) as surname',
                'JSON_UNQUOTE(JSON_EXTRACT(gibbonAdmissionsApplication.data, "$.parent1preferredName")) as preferredName',
                'JSON_UNQUOTE(JSON_EXTRACT(gibbonAdmissionsApplication.data, "$.parent1email")) as email',
                '"" as gibbonPersonID',
                '"Parent" as roleCategory',
                'gibbonAdmissionsApplication.status',
                '"" as image_240',
                'JSON_UNQUOTE(JSON_EXTRACT(gibbonAdmissionsApplication.data, "$.parent1relationship")) as relationship',
                '"" as yearGroup',
                '"" as applicationID',
            ])
            ->from($this->getTableName())
            ->innerJoin('gibbonAdmissionsAccount', 'gibbonAdmissionsApplication.foreignTableID=gibbonAdmissionsAccount.gibbonAdmissionsAccountID')
            ->where('gibbonAdmissionsAccount.gibbonFamilyID IS NULL')
            ->where('gibbonAdmissionsApplication.status <> "Accepted"')
            ->where('gibbonAdmissionsApplication.foreignTable="gibbonAdmissionsAccount"')
            ->where('gibbonAdmissionsApplication.gibbonAdmissionsApplicationID=:gibbonAdmissionsApplicationID')
            ->where('JSON_UNQUOTE(JSON_EXTRACT(gibbonAdmissionsApplication.data, "$.gibbonPersonIDParent1")) IS NULL')
            ->where('JSON_UNQUOTE(JSON_EXTRACT(gibbonAdmissionsApplication.result, "$.gibbonPersonIDParent1")) IS NULL')
            ->bindValue('gibbonAdmissionsApplicationID', $gibbonAdmissionsApplicationID);

        // Application Parent 2, pre-acceptance
        $this->unionAllWithCriteria($query, $criteria)
            ->cols([
                'gibbonAdmissionsApplication.gibbonAdmissionsApplicationID',
                'JSON_UNQUOTE(JSON_EXTRACT(gibbonAdmissionsApplication.data, "$.parent2surname")) as surname',
                'JSON_UNQUOTE(JSON_EXTRACT(gibbonAdmissionsApplication.data, "$.parent2preferredName")) as preferredName',
                'JSON_UNQUOTE(JSON_EXTRACT(gibbonAdmissionsApplication.data, "$.parent2email")) as email',
                '"" as gibbonPersonID',
                '"Parent" as roleCategory',
                'gibbonAdmissionsApplication.status',
                '"" as image_240',
                'JSON_UNQUOTE(JSON_EXTRACT(gibbonAdmissionsApplication.data, "$.parent2relationship")) as relationship',
                '"" as yearGroup',
                '"" as applicationID',
            ])
            ->from($this->getTableName())
            ->innerJoin('gibbonAdmissionsAccount', 'gibbonAdmissionsApplication.foreignTableID=gibbonAdmissionsAccount.gibbonAdmissionsAccountID')
            ->where('gibbonAdmissionsAccount.gibbonFamilyID IS NULL')
            ->where('gibbonAdmissionsApplication.status <> "Accepted"')
            ->where('gibbonAdmissionsApplication.foreignTable="gibbonAdmissionsAccount"')
            ->where('gibbonAdmissionsApplication.gibbonAdmissionsApplicationID=:gibbonAdmissionsApplicationID')
            ->where('JSON_UNQUOTE(JSON_EXTRACT(gibbonAdmissionsApplication.data, "$.parent2surname")) IS NOT NULL')
            ->where('JSON_UNQUOTE(JSON_EXTRACT(gibbonAdmissionsApplication.data, "$.gibbonPersonIDParent2")) IS NULL')
            ->where('JSON_UNQUOTE(JSON_EXTRACT(gibbonAdmissionsApplication.result, "$.gibbonPersonIDParent2")) IS NULL')
            ->bindValue('gibbonAdmissionsApplicationID', $gibbonAdmissionsApplicationID);

        // Family siblings, same admissions account
        $this->unionAllWithCriteria($query, $criteria)
            ->cols([
                'gibbonAdmissionsApplication.gibbonAdmissionsApplicationID',
                'gibbonPerson.surname',
                'gibbonPerson.preferredName',
                'gibbonPerson.email',
                'gibbonPerson.gibbonPersonID',
                '"Student" as roleCategory',
                'gibbonPerson.status',
                'gibbonPerson.image_240',
                '"Sibling" as relationship',
                'gibbonYearGroup.name as yearGroup',
                '"" as applicationID',
            ])
            ->from($this->getTableName())
            ->innerJoin('gibbonAdmissionsAccount', 'gibbonAdmissionsApplication.foreignTableID=gibbonAdmissionsAccount.gibbonAdmissionsAccountID')
            ->innerJoin('gibbonFamily', 'gibbonFamily.gibbonFamilyID=gibbonAdmissionsAccount.gibbonFamilyID')
            ->innerJoin('gibbonFamilyChild', 'gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID')
            ->innerJoin('gibbonPerson', 'gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->leftJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->leftJoin('gibbonYearGroup', 'gibbonYearGroup.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID')
            ->where('gibbonAdmissionsApplication.foreignTable="gibbonAdmissionsAccount"')
            ->where('gibbonAdmissionsApplication.gibbonAdmissionsApplicationID=:gibbonAdmissionsApplicationID')
            ->where('(gibbonAdmissionsApplication.status <> "Accepted" OR JSON_UNQUOTE(JSON_EXTRACT(gibbonAdmissionsApplication.result, "$.gibbonPersonIDStudent")) <> gibbonPerson.gibbonPersonID)')
            ->bindValue('gibbonAdmissionsApplicationID', $gibbonAdmissionsApplicationID)
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        // Application siblings, same admissions account (including self)
        $this->unionAllWithCriteria($query, $criteria)
            ->cols([
                'applications.gibbonAdmissionsApplicationID',
                'JSON_UNQUOTE(JSON_EXTRACT(applications.data, "$.surname")) as surname',
                'JSON_UNQUOTE(JSON_EXTRACT(applications.data, "$.preferredName")) as preferredName',
                'JSON_UNQUOTE(JSON_EXTRACT(applications.data, "$.email")) as email',
                '"" as gibbonPersonID',
                '"Student" as roleCategory',
                'applications.status',
                '"" as image_240',
                '"Sibling" as relationship',
                'gibbonYearGroup.name as yearGroup',
                'applications.gibbonAdmissionsApplicationID as applicationID',
            ])
            ->from($this->getTableName())
            ->innerJoin('gibbonAdmissionsAccount', 'gibbonAdmissionsApplication.foreignTableID=gibbonAdmissionsAccount.gibbonAdmissionsAccountID')
            ->innerJoin('gibbonAdmissionsApplication as applications', 'applications.foreignTableID=gibbonAdmissionsAccount.gibbonAdmissionsAccountID')
            ->leftJoin('gibbonYearGroup', 'gibbonYearGroup.gibbonYearGroupID=applications.gibbonYearGroupID')
            ->where('gibbonAdmissionsApplication.foreignTable="gibbonAdmissionsAccount"')
            ->where('gibbonAdmissionsApplication.gibbonAdmissionsApplicationID=:gibbonAdmissionsApplicationID')
            ->where('applications.status <> "Accepted"')
            ->bindValue('gibbonAdmissionsApplicationID', $gibbonAdmissionsApplicationID);

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
                    gibbonSchoolYear.name as schoolYear,
                    JSON_UNQUOTE(JSON_EXTRACT(gibbonAdmissionsApplication.data, '$.surname')) AS studentSurname,
                    JSON_UNQUOTE(JSON_EXTRACT(gibbonAdmissionsApplication.data, '$.preferredName')) AS studentPreferredName,
                    JSON_UNQUOTE(JSON_EXTRACT(gibbonAdmissionsApplication.data, '$.PaySubmissionFeeComplete')) AS submissionFeeComplete,
                    JSON_UNQUOTE(JSON_EXTRACT(gibbonAdmissionsApplication.data, '$.PayProcessingFeeComplete')) AS processingFeeComplete
                FROM gibbonAdmissionsApplication
                JOIN gibbonForm ON (gibbonAdmissionsApplication.gibbonFormID=gibbonForm.gibbonFormID)
                LEFT JOIN gibbonSchoolYear ON (gibbonAdmissionsApplication.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
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
