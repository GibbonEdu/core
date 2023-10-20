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

namespace Gibbon\Domain\Students;

use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\ScrubbableGateway;
use Gibbon\Domain\Traits\Scrubbable;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\Traits\ScrubByTimestamp;

/**
 * @version v17
 * @since   v17
 */
class ApplicationFormGateway extends QueryableGateway implements ScrubbableGateway
{
    use TableAware;
    use Scrubbable;
    use ScrubByTimestamp;

    private static $tableName = 'gibbonApplicationForm';
    private static $primaryKey = 'gibbonApplicationFormID';

    private static $searchableColumns = ['gibbonApplicationFormID', 'preferredName', 'surname', 'paymentTransactionID'];

    private static $scrubbableKey = 'timestamp';
    private static $scrubbableColumns = ['gibbonApplicationFormHash' => 'randomString', 'dob' => null, 'email' => null, 'homeAddress' => null, 'homeAddressDistrict' => null, 'homeAddressCountry' => null, 'phone1Type' => '', 'phone1CountryCode' => '', 'phone1' => '', 'phone2Type' => '', 'phone2CountryCode' => '', 'phone2' => '', 'countryOfBirth' => '', 'referenceEmail' => null, 'schoolName1' => '', 'schoolAddress1' => '', 'schoolGrades1' => '', 'schoolLanguage1' => '', 'schoolDate1' => null, 'schoolName2' => '', 'schoolAddress2' => '', 'schoolGrades2' => '', 'schoolLanguage2' => '', 'schoolDate2' => null, 'siblingName1' => '', 'siblingDOB1' => null, 'siblingSchool1' => '', 'siblingSchoolJoiningDate1' => null, 'siblingName2' => '', 'siblingDOB2' => null, 'siblingSchool2' => '', 'siblingSchoolJoiningDate2' => null, 'siblingName3' => '', 'siblingDOB3' => null, 'siblingSchool3' => '', 'siblingSchoolJoiningDate3' => null, 'languageHomePrimary' => '', 'languageHomeSecondary' => '', 'languageFirst' => '', 'languageSecond' => '', 'languageThird' => '', 'medicalInformation' => '', 'sen' => null, 'senDetails' => '', 'languageChoice' => null, 'languageChoiceExperience' => '', 'payment' => '', 'companyName' => null, 'companyContact' => null, 'companyAddress' => null, 'companyEmail' => null, 'companyCCFamily' => null, 'companyPhone' => null, 'companyAll' => null, 'gibbonFinanceFeeCategoryIDList' => null, 'parent1languageFirst' => null, 'parent1languageSecond' => null, 'parent1email' => null, 'parent1phone1Type' => null, 'parent1phone1CountryCode' => null, 'parent1phone1' => null, 'parent1phone2Type' => null, 'parent1phone2CountryCode' => null, 'parent1phone2' => null, 'parent1profession' => null, 'parent1employer' => null, 'parent2languageFirst' => null, 'parent2languageSecond' => null, 'parent2email' => null, 'parent2phone1Type' => null, 'parent2phone1CountryCode' => null, 'parent2phone1' => null, 'parent2phone2Type' => null, 'parent2phone2CountryCode' => null, 'parent2phone2' => null, 'parent2profession' => null, 'parent2employer' => null, 'notes' => '', 'fields' => '', 'parent1fields' => '', 'parent2fields' => ''];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryApplicationFormsBySchoolYear(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonApplicationFormID', 'gibbonApplicationForm.status', 'preferredName', 'surname', 'dob', 'priority', 'gibbonApplicationForm.timestamp', 'milestones', 'gibbonFamilyID', 'schoolName1', 'schoolDate1', 'schoolName2', 'schoolDate2', 'parent1title', 'parent1preferredName', 'parent1surname', 'parent1email', 'parent2title', 'parent2preferredName', 'parent2surname', 'parent2email', 'paymentMade','gibbonYearGroup.name AS yearGroup', 'gibbonPayment.paymentTransactionID'
            ])
            ->innerJoin('gibbonYearGroup', 'gibbonApplicationForm.gibbonYearGroupIDEntry=gibbonYearGroup.gibbonYearGroupID')
            ->leftJoin('gibbonPayment', "gibbonApplicationForm.gibbonPaymentID=gibbonPayment.gibbonPaymentID AND gibbonPayment.foreignTable='gibbonApplicationForm'")
            ->where('gibbonApplicationForm.gibbonSchoolYearIDEntry  = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        $criteria->addFilterRules([
            'status' => function ($query, $status) {
                return $query
                    ->where('gibbonApplicationForm.status = :status')
                    ->bindValue('status', ucwords($status));
            },

            'paid' => function ($query, $paymentMade) {
                return $query
                    ->where('gibbonApplicationForm.paymentMade = :paymentMade')
                    ->bindValue('paymentMade', ucfirst($paymentMade));
            },

            'formGroup' => function ($query, $value) {
                return $query
                    ->where(strtoupper($value) == 'Y'
                        ? 'gibbonApplicationForm.gibbonFormGroupID IS NOT NULL'
                        : 'gibbonApplicationForm.gibbonFormGroupID IS NULL');
            },

            'yearGroup' => function ($query, $gibbonYearGroupIDEntry) {
                return $query
                    ->where('gibbonApplicationForm.gibbonYearGroupIDEntry = :gibbonYearGroupIDEntry')
                    ->bindValue('gibbonYearGroupIDEntry', $gibbonYearGroupIDEntry);
            },

        ]);

        return $this->runQuery($query, $criteria);
    }

    public function selectLinkedApplicationsByID($gibbonApplicationFormID)
    {
        $data = array('gibbonApplicationFormID' => $gibbonApplicationFormID);
        $sql = "SELECT DISTINCT gibbonApplicationFormID, preferredName, surname, status 
                FROM gibbonApplicationForm
                JOIN gibbonApplicationFormLink ON (
                    gibbonApplicationForm.gibbonApplicationFormID=gibbonApplicationFormLink.gibbonApplicationFormID1 OR gibbonApplicationForm.gibbonApplicationFormID=gibbonApplicationFormLink.gibbonApplicationFormID2)
                WHERE gibbonApplicationFormID1=:gibbonApplicationFormID
                OR gibbonApplicationFormID2=:gibbonApplicationFormID 
                ORDER BY gibbonApplicationFormID";

        return $this->db()->select($sql, $data);
    }
}
