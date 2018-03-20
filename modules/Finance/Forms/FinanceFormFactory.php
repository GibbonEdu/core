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

namespace Gibbon\Finance\Forms;

use Gibbon\Forms\DatabaseFormFactory;

/**
 * FinanceFormFactory
 *
 * @version v16
 * @since   v16
 */
class FinanceFormFactory extends DatabaseFormFactory
{
    /**
     * Create and return an instance of DatabaseFormFactory.
     * @return  object DatabaseFormFactory
     */
    public static function create(\Gibbon\sqlConnection $pdo = null)
    {
        return new FinanceFormFactory($pdo);
    }

    public function createSelectInvoicee($name, $gibbonSchoolYearID = '', $params = array())
    {
        $values = array();

        $data = array();
        $sql = "SELECT gibbonFinanceInvoiceeID, preferredName, surname, gibbonRollGroup.nameShort AS rollGroupName, dayType 
                FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup, gibbonFinanceInvoicee
                WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID 
                AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID 
                AND gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID";
        
        if (!empty($gibbonSchoolYearID)) {
            $data['gibbonSchoolYearID'] = $gibbonSchoolYearID;
            $sql .= " AND status='Full' AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID";
        }

        $sql .= " ORDER BY rollGroupName, surname, preferredName";

        $results = $this->pdo->executeQuery($data, $sql);

        $byRollGroup = array();
        if ($results && $results->rowCount() > 0) {
            while ($student = $results->fetch()) {
                $byRollGroup[$student['gibbonFinanceInvoiceeID']] = $student['rollGroupName'].' - '.formatName('', $student['preferredName'], $student['surname'], 'Student', true);
            }
        }

        $values[__('All Enrolled Students by Roll Group')] = $byRollGroup;
                
        return $this->createSelect($name)->fromArray($values)->placeholder();
    }

    public function createSelectInvoiceStatus($name, $currentStatus = 'All')
    {
        if ($currentStatus == 'Pending' || $currentStatus == 'Cancelled' || $currentStatus == 'Refunded') {
            return $this->createTextField($name)->readonly()->setValue(__($currentStatus));
        }

        $statuses = array();
        if ($currentStatus == 'All') {
            $statuses = array(
                '%'                => __('All'),
                'Pending'          => __('Pending'),
                'Issued'           => __('Issued'),
                'Issued - Overdue' => __('Issued - Overdue'),
                'Paid'             => __('Paid'),
                'Paid - Partial'   => __('Paid - Partial'),
                'Paid - Late'      => __('Paid - Late'),
                'Cancelled'        => __('Cancelled'),
                'Refunded'         => __('Refunded'),
            );
        } else if ($currentStatus == 'Issued') {
            $statuses = array(
                'Issued'         => __('Issued'),
                'Paid'           => __('Paid'),
                'Paid - Partial' => __('Paid - Partial'),
                'Cancelled'      => __('Cancelled'),
            );
        } else if ($currentStatus == 'Paid') {
            $statuses = array(
                'Paid'     => __('Paid'),
                'Refunded' => __('Refunded'),
            );
        } else if ($currentStatus == 'Paid - Partial') {
            $statuses = array(
                'Paid - Partial'  => __('Paid - Partial'),
                'Paid - Complete' => __('Paid - Complete'),
                'Refunded'        => __('Refunded'),
            );
        }

        return $this->createSelect($name)->fromArray($statuses)->placeholder();
    }

    public function createSelectBillingSchedule($name, $gibbonSchoolYearID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT gibbonFinanceBillingScheduleID as value, name FROM gibbonFinanceBillingSchedule 
                WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name";

        return $this->createSelect($name)->fromQuery($this->pdo, $sql, $data)->fromArray(array('Ad Hoc' => __('Ad Hoc')))->placeholder();
    }

    public function createSelectFee($name, $gibbonSchoolYearID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT gibbonFinanceFeeCategory.name as groupBy, gibbonFinanceFee.gibbonFinanceFeeID as value, gibbonFinanceFee.name 
                FROM gibbonFinanceFee 
                JOIN gibbonFinanceFeeCategory ON (gibbonFinanceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) 
                WHERE gibbonFinanceFee.gibbonSchoolYearID=:gibbonSchoolYearID
                ORDER BY gibbonFinanceFeeCategory.name, gibbonFinanceFee.name";

        return $this->createSelect($name)
            ->fromArray(array('' => __('Choose a fee to add it')))
            ->fromArray(array('Ad Hoc Fee' => __('Ad Hoc Fee')))
            ->fromQuery($this->pdo, $sql, $data, 'groupBy');
    }

    public function createSelectFeeCategory($name)
    {
        $sql = "SELECT gibbonFinanceFeeCategoryID as value, name FROM gibbonFinanceFeeCategory WHERE active='Y' ORDER BY (gibbonFinanceFeeCategoryID=1) DESC, name";

        return $this->createSelect($name)->fromQuery($this->pdo, $sql);
    }

    public function createSelectPaymentMethod($name)
    {
        $methods = array(
            'Online'        => __('Online'),
            'Bank Transfer' => __('Bank Transfer'),
            'Cash'          => __('Cash'),
            'Cheque'        => __('Cheque'),
            'Credit Card'   => __('Credit Card'),
            'Other'         => __('Other')
        );

        return $this->createSelect($name)->fromArray($methods)->placeholder();
    }

    public function createSelectMonth($name)
    {
        $months = array_reduce(range(1,12), function($group, $item){
            $month = date('m', mktime(0, 0, 0, $item, 1, 0));
            $group[$month] = $month.' - '.date('F', mktime(0, 0, 0, $item, 1, 0));
            return $group;
        }, array());

        return $this->createSelect($name)->fromArray($months)->placeholder();
    }

    public function createRowEmail($name)
    {
        return $this->createRow();
    }
}
