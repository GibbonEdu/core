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

use Gibbon\Forms\Form;
use Gibbon\Module\Finance\Forms\FinanceFormFactory;
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/invoices_manage_issue.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    //Check if school year specified
    $gibbonSchoolYearID = isset($_GET['gibbonSchoolYearID'])? $_GET['gibbonSchoolYearID'] : '';
    $gibbonFinanceInvoiceID = isset($_GET['gibbonFinanceInvoiceID'])? $_GET['gibbonFinanceInvoiceID'] : '';
    $status = isset($_GET['status'])? $_GET['status'] : '';
    $gibbonFinanceInvoiceeID = isset($_GET['gibbonFinanceInvoiceeID'])? $_GET['gibbonFinanceInvoiceeID'] : '';
    $monthOfIssue = isset($_GET['monthOfIssue'])? $_GET['monthOfIssue'] : '';
    $gibbonFinanceBillingScheduleID = isset($_GET['gibbonFinanceBillingScheduleID'])? $_GET['gibbonFinanceBillingScheduleID'] : '';
    $gibbonFinanceFeeCategoryID = isset($_GET['gibbonFinanceFeeCategoryID'])? $_GET['gibbonFinanceFeeCategoryID'] : '';

    $urlParams = compact('gibbonSchoolYearID', 'status', 'gibbonFinanceInvoiceeID', 'monthOfIssue', 'gibbonFinanceBillingScheduleID', 'gibbonFinanceFeeCategoryID'); 

    $page->breadcrumbs
        ->add(__('Manage Invoices'), 'invoices_manage.php', $urlParams)
        ->add(__('Issue Invoice'));       

    echo '<p>';
    echo __('Issuing an invoice confirms it in the system, meaning the financial details within the invoice can no longer be edited. On issue, you also have the choice to email the invoice to the appropriate family and company recipients.');
    echo '</p>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, array('error4' => 'Some aspects of your request failed, but others were successful. Because of the errors, the system did not attempt to send any requested emails.'));
    }

    if ($gibbonFinanceInvoiceID == '' or $gibbonSchoolYearID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID);
            $sql = "SELECT gibbonFinanceInvoice.*, companyName, companyContact, companyEmail, companyCCFamily, gibbonSchoolYear.name as schoolYear, gibbonPerson.surname, gibbonPerson.preferredName, gibbonFinanceBillingSchedule.name as billingScheduleName, gibbonFinanceBillingSchedule.invoiceDueDate as billingScheduleInvoiceDueDate
					FROM gibbonFinanceInvoice 
					JOIN gibbonSchoolYear ON (gibbonSchoolYear.gibbonSchoolYearID=gibbonFinanceInvoice.gibbonSchoolYearID)
					LEFT JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoice.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID) 
					LEFT JOIN gibbonFinanceBillingSchedule ON (gibbonFinanceBillingSchedule.gibbonFinanceBillingScheduleID=gibbonFinanceInvoice.gibbonFinanceBillingScheduleID)
					LEFT JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonFinanceInvoicee.gibbonPersonID)
					WHERE gibbonFinanceInvoice.gibbonSchoolYearID=:gibbonSchoolYearID 
					AND gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID
					AND gibbonFinanceInvoice.status='Pending'";
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $values = $result->fetch();

            if ($status != '' or $gibbonFinanceInvoiceeID != '' or $monthOfIssue != '' or $gibbonFinanceBillingScheduleID != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Finance/invoices_manage.php&".http_build_query($urlParams)."'>".__('Back to Search Results').'</a>';
                echo '</div>';
			}
			
			$form = Form::create('invoice', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/invoices_manage_issueProcess.php?'.http_build_query($urlParams));
			$form->setFactory(FinanceFormFactory::create($pdo));
			
			$form->addHiddenValue('address', $_SESSION[$guid]['address']);
            $form->addHiddenValue('gibbonFinanceInvoiceID', $gibbonFinanceInvoiceID);

			$form->addRow()->addHeading(__('Basic Information'));

			$row = $form->addRow();
                $row->addLabel('schoolYear', __('School Year'));
				$row->addTextField('schoolYear')->required()->readonly();
				
			$row = $form->addRow();
                $row->addLabel('personName', __('Invoicee'));
                $row->addTextField('personName')->required()->readonly()->setValue(Format::name('', $values['preferredName'], $values['surname'], 'Student', true));

            $form->addHiddenValue('billingScheduleType', $values['billingScheduleType']);

            $row = $form->addRow();
                $row->addLabel('billingScheduleTypeText', __('Scheduling'));
				$row->addTextField('billingScheduleTypeText')->required()->readonly()->setValue(__($values['billingScheduleType']));
				
			if ($values['billingScheduleType'] == 'Scheduled') {
				$row = $form->addRow();
					$row->addLabel('billingScheduleName', __('Billing Schedule'));
					$row->addTextField('billingScheduleName')->required()->readonly();
					$form->addHiddenValue('invoiceDueDate', dateConvertBack($guid, $values['billingScheduleInvoiceDueDate']));
			} else {
				$row = $form->addRow();
					$row->addLabel('invoiceDueDate', __('Invoice Due Date'));
					$row->addDate('invoiceDueDate')->required()->readonly();
			}

            $form->addHiddenValue('status', $values['status']);

			$row = $form->addRow();
				$row->addLabel('statusText', __('Status'));
				$row->addTextField('statusText')->required()->readonly()->setValue(__($values['status']));

			$row = $form->addRow();
                $row->addLabel('notes', __('Notes'))->description(__('Notes will be displayed on the final invoice and receipt.'));
				$row->addTextArea('notes')->setRows(5);
				
			$form->addRow()->addHeading(__('Fees'));

			$totalFee = getInvoiceTotalFee($pdo, $gibbonFinanceInvoiceID, $values['status']);
			$row = $form->addRow();
				$row->addLabel('totalFee', __('Total'))->description('<small><i>('.$_SESSION[$guid]['currency'].')</i></small>');
				$row->addTextField('totalFee')->required()->readonly()->setValue(number_format($totalFee, 2));

                        $form->addHiddenValue('invoiceTo', $values['invoiceTo']);
                        
			$row = $form->addRow();
				$row->addLabel('invoiceToText', __('Invoice To'));
				$row->addTextField('invoiceToText')->required()->readonly()->setValue(__($values['invoiceTo']));

			$form->addRow()->addHeading(__('Email Invoice'));

			$email = getSettingByScope($connection2, 'Finance', 'email');
			$form->addHiddenValue('email', $email);
			if (empty($email)) {
				$form->addRow()->addAlert(__('An outgoing email address has not been set up under Invoice & Receipt Settings, and so no emails can be sent.'), 'error');
			} else {
				$form->addRow()->addInvoiceEmailCheckboxes('emails[]', 'names[]', $values, $gibbon->session);
			}

			$row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();
        }
    }
}
