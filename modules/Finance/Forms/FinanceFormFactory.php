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

        // Opt Groups
        $byRollGroup = __('All Enrolled Students by Roll Group');
        $byName = __('All Enrolled Students by Alphabet');

        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT gibbonFinanceInvoiceeID, preferredName, surname, gibbonRollGroup.nameShort AS rollGroupName, dayType 
                FROM gibbonPerson
                JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID)
                WHERE gibbonPerson.status='Full' 
                AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                ORDER BY LENGTH(gibbonRollGroup.name), gibbonRollGroup.name, surname, preferredName";

        $results = $this->pdo->executeQuery($data, $sql);
        $students = ($results->rowCount() > 0)? $results->fetchAll() : array();

        // Add students by Roll Group and Name
        foreach ($students as $student) {
            $fullName = formatName('', $student['preferredName'], $student['surname'], 'Student', true);

            $values[$byRollGroup][$student['gibbonFinanceInvoiceeID']] = $student['rollGroupName'].' - '.$fullName;
            $values[$byName][$student['gibbonFinanceInvoiceeID']] = $fullName.' - '.$student['rollGroupName'];
        }

        // Sort the byName list so it's not byRollGroup
        if (!empty($values[$byName]) && is_array($values[$byName])) {
            asort($values[$byName]);
        }

        // Add students by Day Type (optionally)
        $dayTypeOptions = getSettingByScope($this->pdo->getConnection(), 'User Admin', 'dayTypeOptions');
        if (!empty($dayTypeOptions)) {
            $dayTypes = explode(',', $dayTypeOptions);

            foreach ($students as $student) {
                if (empty($student['dayType']) || !in_array($student['dayType'], $dayTypes)) continue;

                $byDayType = $student['dayType'].' '.__('Students by Roll Groups');
                $fullName = formatName('', $student['preferredName'], $student['surname'], 'Student', true);
    
                $values[$byDayType][$student['gibbonFinanceInvoiceeID']] = $student['rollGroupName'].' - '.$fullName;
            }
        }
                
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

        return $this->createSelect($name)->fromArray($statuses);
    }

    public function createSelectBillingSchedule($name, $gibbonSchoolYearID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT gibbonFinanceBillingScheduleID as value, name FROM gibbonFinanceBillingSchedule 
                WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name";

        return $this->createSelect($name)->fromQuery($this->pdo, $sql, $data)->placeholder();
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

    public function createInvoiceEmailCheckboxes($checkboxName, $hiddenValueName, $values, $session) 
    {
        $table = $this->createTable()->setClass('fullWidth');

        // Company Emails
        if ($values['invoiceTo'] == 'Company') {
            if (empty($values['companyEmail']) || empty($values['companyContact']) || empty($values['companyName'])) {
                $table->addRow()->addTableCell(__('There is no company contact available to send this invoice to.'))->colSpan(2)->wrap('<div class="warning">', '</div>');
            } else {
                $row = $table->addRow();
                    $row->addLabel($checkboxName, $values['companyContact'])->description($values['companyName']);
                    $row->addCheckbox($checkboxName)
                        ->description($values['companyEmail'])
                        ->setValue($values['companyEmail'])
                        ->append('<input type="hidden" name="'.$hiddenValueName.'" value="'.$values['companyContact'].'">');
            }
        }

        // Family Emails
        if ($values['invoiceTo'] == 'Family' || ($values['invoiceTo'] == 'Company' && $values['companyCCFamily'] == 'Y')) {
            $data = array('gibbonFinanceInvoiceeID' => $values['gibbonFinanceInvoiceeID']);
            $sql = "SELECT parent.title, parent.surname, parent.preferredName, parent.email, gibbonFamilyRelationship.relationship
                    FROM gibbonFinanceInvoicee 
                    JOIN gibbonPerson AS student ON (gibbonFinanceInvoicee.gibbonPersonID=student.gibbonPersonID) 
                    JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=student.gibbonPersonID) 
                    JOIN gibbonFamilyAdult ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID) 
                    JOIN gibbonPerson AS parent ON (gibbonFamilyAdult.gibbonPersonID=parent.gibbonPersonID) 
                    LEFT JOIN gibbonFamilyRelationship ON (gibbonFamilyRelationship.gibbonFamilyID=gibbonFamilyChild.gibbonFamilyID && gibbonFamilyRelationship.gibbonPersonID1=parent.gibbonPersonID && gibbonFamilyRelationship.gibbonPersonID2=student.gibbonPersonID)
                    WHERE gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID 
                    AND (contactPriority=1 OR (contactPriority=2 AND contactEmail='Y')) 
                    GROUP BY parent.gibbonPersonID
                    ORDER BY contactPriority, surname, preferredName";

            $result = $this->pdo->executeQuery($data, $sql);

            if ($result->rowCount() == 0) {
                $table->addRow()->addTableCell(__('There are no family members available to send this receipt to.'))->colSpan(2)->wrap('<div class="warning">', '</div>');
            } else {
                while ($person = $result->fetch()) {
                    $name = formatName(htmlPrep($person['title']), htmlPrep($person['preferredName']), htmlPrep($person['surname']), 'Parent', false);
                    $row = $table->addRow();
                        $row->addLabel($checkboxName, $name)->description($values['invoiceTo'] == 'Company'? __('(Family CC)') : '')->description($person['relationship']);
                        $row->onlyIf(!empty($person['email']))
                            ->addCheckbox($checkboxName)
                            ->description($person['email'])
                            ->setValue($person['email'])
                            ->checked($person['email'])
                            ->append('<input type="hidden" name="'.$hiddenValueName.'" value="'.$name.'">');
                        $row->onlyIf(empty($person['email']))
                            ->addContent(__('No email address.'))
                            ->addClass('right')
                            ->wrap('<span class="small emphasis">', '</span>');
                }
            }
        }

        // CC Self
        if (!empty($session->get('email'))) {
            $name = formatName('', htmlPrep($session->get('preferredName')), htmlPrep($session->get('surname')), 'Parent', false);
            $row = $table->addRow()->addClass('emailReceiptSection');
                $row->addLabel($checkboxName, $name)->description(__('(CC Self?)'));
                $row->addCheckbox($checkboxName)
                    ->description($session->get('email'))
                    ->setValue($session->get('email'))
                    ->append('<input type="hidden" name="'.$hiddenValueName.'" value="'.$name.'">');
        }

        return $table;
    }
}
