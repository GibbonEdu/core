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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_payment.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Generate Invoices'));

    echo '<h2>';
    echo __('Invoices Not Yet Generated');
    echo '</h2>';
    echo '<p>';
    echo sprintf(__('The list below shows students who have been accepted for an activity in the current year, who have yet to have invoices generated for them. You can generate invoices to a given %1$sBilling Schedule%2$s, or you can simulate generation (e.g. mark them as generated, but not actually produce an invoice).'), "<a href='".$session->get('absoluteURL')."/index.php?q=/modules/Finance/billingSchedule_manage.php'>", '</a>');
    echo '</p>';

    
        $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonSchoolYearID2' => $session->get('gibbonSchoolYearID'), 'today' => date('Y-m-d'));
        $sql = "SELECT gibbonActivityStudentID, gibbonPerson.gibbonPersonID, surname, preferredName, gibbonFormGroup.nameShort AS formGroup, gibbonActivityStudent.status, payment, paymentType, gibbonActivity.name, programStart, programEnd FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL  OR dateEnd>=:today) AND gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID2 AND gibbonActivityStudent.status='Accepted' AND payment>0 AND invoiceGenerated='N' ORDER BY surname, preferredName, name";
        $result = $connection2->prepare($sql);
        $result->execute($data);

    if ($result->rowCount() < 1) {
        echo $page->getBlankSlate();
    } else {
        $lastPerson = '';

        $form = Form::create('generateInvoices', $session->get('absoluteURL').'/modules/'.$session->get('module').'/activities_paymentProcessBulk.php');
        $form->addConfirmation(__('Are you sure you wish to process this action? It cannot be undone.'));
        $form->setClass('w-full blank bulkActionForm');
        $form->addHiddenValue('address', $session->get('address'));

        $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
        $sql = "SELECT gibbonFinanceBillingScheduleID as value, name FROM gibbonFinanceBillingSchedule WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name";
        $resultSchedule = $pdo->executeQuery($data, $sql);

        $billingSchedules = ($resultSchedule->rowCount() > 0)? $resultSchedule->fetchAll(\PDO::FETCH_KEY_PAIR) : array();
        $billingSchedules = array_map(function($item) {
            return sprintf(__('Generate Invoices To %1$s'), $item);
        }, $billingSchedules);
        $defaultActions = array('Generate Invoice - Simulate' => __('Generate Invoice - Simulate'));

        $row = $form->addRow();
            $bulkAction = $row->addColumn()->addClass('flex justify-end items-center');
            $bulkAction->addSelect('action')
                ->fromArray($billingSchedules)
                ->fromArray($defaultActions)
                ->required()
                ->setClass('mediumWidth floatNone')
                ->placeholder(__('Select action'));
            $bulkAction->addSubmit(__('Go'));

        $table = $form->addRow()->addTable()->addClass('colorOddEven');

        $header = $table->addHeaderRow();
        $header->addContent(__('Form Group'));
        $header->addContent(__('Student'));
        $header->addContent(__('Activity'));
        $header->addContent(__('Cost'))->append('<br/><span class="small emphasis">'.$session->get('currency').'</span>');
        $header->addCheckbox('checkall')->setClass('floatNone textCenter checkall');

        while ($student = $result->fetch()) {
            $gibbonActivityStudentID = $student['gibbonActivityStudentID'];

            $row = $table->addRow();
            $row->addContent($student['formGroup']);
            $row->addContent(Format::name('', $student['preferredName'], $student['surname'], 'Student', true));
            $row->addContent($student['name']);
            $row->addCurrency("payment[$gibbonActivityStudentID]")->required()->setValue($student['payment']);
            $row->addCheckbox("gibbonActivityStudentID[$gibbonActivityStudentID]")->setValue($student['gibbonActivityStudentID'])->setClass('bulkCheckbox');
        }

        echo $form->getOutput();
    }

    echo '<h2>';
    echo __('Invoices Generated');
    echo '</h2>';

    
        $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonSchoolYearID2' => $session->get('gibbonSchoolYearID'), 'today' => date('Y-m-d'));
        $sql = "SELECT gibbonPerson.gibbonPersonID, studentID, surname, preferredName, gibbonFormGroup.nameShort AS formGroup, gibbonActivityStudent.status, payment, gibbonActivity.name, programStart, programEnd, gibbonFinanceInvoiceID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL  OR dateEnd>=:today) AND gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID2 AND gibbonActivityStudent.status='Accepted' AND payment>0 AND invoiceGenerated='Y' ORDER BY surname, preferredName, name";
        $result = $connection2->prepare($sql);
        $result->execute($data);

    if ($result->rowCount() < 1) {
        echo $page->getBlankSlate();
    } else {
        $lastPerson = '';

        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __('Form Group');
        echo '</th>';
        echo '<th>';
        echo __('Student');
        echo '</th>';
        echo '<th>';
        echo __('Activity');
        echo '</th>';
        echo '<th>';
        echo __('Invoice Number').'<br/>';
        echo '</th>';
        echo '</tr>';

        $count = 0;
        $rowNum = 'odd';
        while ($row = $result->fetch()) {
            if ($count % 2 == 0) {
                $rowNum = 'even';
            } else {
                $rowNum = 'odd';
            }
            ++$count;

            //COLOR ROW BY STATUS!
            echo "<tr class=$rowNum>";
            echo '<td>';
            echo $row['formGroup'];
            echo '</td>';
            echo '<td>';
            echo Format::name('', $row['preferredName'], $row['surname'], 'Student', true);
            echo '</td>';
            echo '<td>';
            echo $row['name'];
            echo '</td>';
            echo '<td>';
            $invoiceNumber = $container->get(SettingGateway::class)->getSettingByScope('Finance', 'invoiceNumber');
            if ($invoiceNumber == 'Person ID + Invoice ID') {
                echo ltrim($row['gibbonPersonID'] ?? '', '0').'-'.ltrim($row['gibbonFinanceInvoiceID'] ?? '', '0');
            } elseif ($invoiceNumber == 'Student ID + Invoice ID') {
                echo ltrim($row['studentID'] ?? '', '0').'-'.ltrim($row['gibbonFinanceInvoiceID'] ?? '', '0');
            } else {
                echo ltrim($row['gibbonFinanceInvoiceID'] ?? '', '0');
            }
            echo '</td>';
            echo '</tr>';

            $lastPerson = $row['gibbonPersonID'];
        }
        if ($count == 0) {
            echo "<tr class=$rowNum>";
            echo '<td colspan=4>';
            echo __('There are no records to display.');
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}
?>
