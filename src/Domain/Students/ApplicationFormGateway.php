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

namespace Gibbon\Domain\Students;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * @version v17
 * @since   v17
 */
class ApplicationFormGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonApplicationForm';

    private static $searchableColumns = ['gibbonApplicationFormID', 'preferredName', 'surname', 'paymentTransactionID'];
    
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

            'rollGroup' => function ($query, $value) {
                return $query
                    ->where(strtoupper($value) == 'Y'
                        ? 'gibbonApplicationForm.gibbonRollGroupID IS NOT NULL'
                        : 'gibbonApplicationForm.gibbonRollGroupID IS NULL');
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
