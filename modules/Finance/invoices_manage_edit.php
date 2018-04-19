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
use Gibbon\Finance\Forms\FinanceFormFactory;

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/invoices_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Check if school year specified
    $gibbonSchoolYearID = isset($_GET['gibbonSchoolYearID'])? $_GET['gibbonSchoolYearID'] : '';
    $gibbonFinanceInvoiceID = isset($_GET['gibbonFinanceInvoiceID'])? $_GET['gibbonFinanceInvoiceID'] : '';
    $status = isset($_GET['status'])? $_GET['status'] : '';
    $gibbonFinanceInvoiceeID = isset($_GET['gibbonFinanceInvoiceeID'])? $_GET['gibbonFinanceInvoiceeID'] : '';
    $monthOfIssue = isset($_GET['monthOfIssue'])? $_GET['monthOfIssue'] : '';
    $gibbonFinanceBillingScheduleID = isset($_GET['gibbonFinanceBillingScheduleID'])? $_GET['gibbonFinanceBillingScheduleID'] : '';
    $gibbonFinanceFeeCategoryID = isset($_GET['gibbonFinanceFeeCategoryID'])? $_GET['gibbonFinanceFeeCategoryID'] : '';

    $linkParams = compact('gibbonSchoolYearID', 'status', 'gibbonFinanceInvoiceeID', 'monthOfIssue', 'gibbonFinanceBillingScheduleID', 'gibbonFinanceFeeCategoryID'); 

    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Finance/invoices_manage.php&'.http_build_query($linkParams)."'>".__($guid, 'Manage Invoices')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Invoice').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, array('success0' => 'Your request was completed successfully.', 'success1' => 'Your request was completed successfully, but one or more requested emails could not be sent.', 'error3' => 'Some elements of your request failed, but others were successful.'));
    }

    if ($gibbonFinanceInvoiceID == '' or $gibbonSchoolYearID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID);
            $sql = "SELECT gibbonFinanceInvoice.*, companyName, companyContact, companyEmail, companyCCFamily, gibbonSchoolYear.name as schoolYear, gibbonPerson.surname, gibbonPerson.preferredName, gibbonFinanceBillingSchedule.name as billingScheduleName
                    FROM gibbonFinanceInvoice 
                    JOIN gibbonSchoolYear ON (gibbonSchoolYear.gibbonSchoolYearID=gibbonFinanceInvoice.gibbonSchoolYearID)
                    LEFT JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoice.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID) 
                    LEFT JOIN gibbonFinanceBillingSchedule ON (gibbonFinanceBillingSchedule.gibbonFinanceBillingScheduleID=gibbonFinanceInvoice.gibbonFinanceBillingScheduleID)
                    LEFT JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonFinanceInvoicee.gibbonPersonID)
                    WHERE gibbonFinanceInvoice.gibbonSchoolYearID=:gibbonSchoolYearID 
                    AND gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            $values = $result->fetch();

            if ($status != '' or $gibbonFinanceInvoiceeID != '' or $monthOfIssue != '' or $gibbonFinanceBillingScheduleID != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Finance/invoices_manage.php&".http_build_query($linkParams)."'>".__($guid, 'Back to Search Results').'</a>';
                echo '</div>';
            }
        
            $form = Form::create('invoice', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/invoices_manage_editProcess.php?'.http_build_query($linkParams));
            $form->setFactory(FinanceFormFactory::create($pdo));

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);
            $form->addHiddenValue('gibbonFinanceInvoiceID', $gibbonFinanceInvoiceID);

            $form->addRow()->addHeading(__('Basic Information'));

            $row = $form->addRow();
                $row->addLabel('schoolYear', __('School Year'));
                $row->addTextField('schoolYear')->isRequired()->readonly();

            $row = $form->addRow();
                $row->addLabel('personName', __('Invoicee'));
                $row->addTextField('personName')->isRequired()->readonly()->setValue(formatName('', $values['preferredName'], $values['surname'], 'Student', true));

            $row = $form->addRow();
                $row->addLabel('billingScheduleType', __('Scheduling'));
                $row->addTextField('billingScheduleType')->isRequired()->readonly();

            if ($values['billingScheduleType'] == 'Scheduled') {
                $row = $form->addRow();
                    $row->addLabel('billingScheduleName', __('Billing Schedule'));
                    $row->addTextField('billingScheduleName')->isRequired()->readonly();
            } else {
                if ($values['status'] == 'Pending' || $values['status'] == 'Issued') {
                    $row = $form->addRow();
                        $row->addLabel('invoiceDueDate', __('Invoice Due Date'));
                        $row->addDate('invoiceDueDate')->isRequired();
                } else {
                    $row = $form->addRow();
                        $row->addLabel('invoiceDueDate', __('Invoice Due Date'));
                        $row->addDate('invoiceDueDate')->isRequired()->readonly();
                }
            }

            $row = $form->addRow();
                $row->addLabel('status', __('Status'))->description($values['status'] == 'Pending'
                    ? __('This value cannot be changed. Use the Issue function to change the status from "Pending" to "Issued".') 
                    : __('Available options are limited according to current status.'));
                $row->addSelectInvoiceStatus('status', $values['status'])->isRequired();

            // PAYMENT INFO
            if ($values['status'] == 'Issued' or $values['status'] == 'Paid - Partial') {
                $form->toggleVisibilityByClass('paymentInfo')->onSelect('status')->when(array('Paid', 'Paid - Partial', 'Paid - Complete'));
                
                $row = $form->addRow()->addClass('paymentInfo');
                    $row->addLabel('paymentType', __('Payment Type'));
                    $row->addSelectPaymentMethod('paymentType')->isRequired();       

                $row = $form->addRow()->addClass('paymentInfo');
                    $row->addLabel('paymentTransactionID', __('Transaction ID'))->description(__('Transaction ID to identify this payment.'));
                    $row->addTextField('paymentTransactionID')->maxLength(50);

                $row = $form->addRow()->addClass('paymentInfo');
                    $row->addLabel('paidDate', __('Date Paid'))->description(__('Date of payment, not entry to system.'));
                    $row->addDate('paidDate')->isRequired();

                $remainingFee = getInvoiceTotalFee($pdo, $gibbonFinanceInvoiceID, $values['status']);
                if ($values['status'] == 'Paid - Partial') {
                    $alreadyPaid = getAmountPaid($connection2, $guid, 'gibbonFinanceInvoice', $gibbonFinanceInvoiceID);
                    $remainingFee -= $alreadyPaid;
                }

                $row = $form->addRow()->addClass('paymentInfo');
                    $row->addLabel('paidAmount', __('Amount Paid'))->description(__('Amount in current payment.'));
                    $row->addCurrency('paidAmount')->maxLength(14)->isRequired()->setValue(number_format($remainingFee, 2, '.', ''));

                unset($values['paidDate']);
                unset($values['paidAmount']);
            }

            $row = $form->addRow();
                $row->addLabel('notes', __('Notes'))->description(__('Notes will be displayed on the final invoice and receipt.'));
                $row->addTextArea('notes')->setRows(5);

            // FEES
            $form->addRow()->addHeading(__('Fees'));

            // Ad Hoc OR Issued (Fixed Fees)
            $dataFees = array('gibbonFinanceInvoiceID' => $values['gibbonFinanceInvoiceID']);
            $sqlFees = "SELECT gibbonFinanceInvoiceFee.gibbonFinanceInvoiceFeeID, gibbonFinanceInvoiceFee.feeType, gibbonFinanceFeeCategory.name AS category, gibbonFinanceInvoiceFee.name AS name, gibbonFinanceInvoiceFee.fee, gibbonFinanceInvoiceFee.description AS description, NULL AS gibbonFinanceFeeID, gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID AS gibbonFinanceFeeCategoryID, sequenceNumber FROM gibbonFinanceInvoiceFee JOIN gibbonFinanceFeeCategory ON (gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID";

            // Union with Standard (Flexible Fees)
            if ($values['status'] == 'Pending') {
                $sqlFees = "(".$sqlFees." AND feeType='Ad Hoc')";
                $sqlFees .= " UNION ";
                $sqlFees .= "(SELECT gibbonFinanceInvoiceFee.gibbonFinanceInvoiceFeeID, gibbonFinanceInvoiceFee.feeType, gibbonFinanceFeeCategory.name AS category, gibbonFinanceFee.name AS name, gibbonFinanceFee.fee AS fee, gibbonFinanceFee.description AS description, gibbonFinanceInvoiceFee.gibbonFinanceFeeID AS gibbonFinanceFeeID, gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID AS gibbonFinanceFeeCategoryID, sequenceNumber FROM gibbonFinanceInvoiceFee JOIN gibbonFinanceFee ON (gibbonFinanceInvoiceFee.gibbonFinanceFeeID=gibbonFinanceFee.gibbonFinanceFeeID) JOIN gibbonFinanceFeeCategory ON (gibbonFinanceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID AND feeType='Standard')";
            }

            $sqlFees .= " ORDER BY sequenceNumber";
            $resultFees = $pdo->executeQuery($dataFees, $sqlFees);

            // CUSTOM BLOCKS
            if ($values['status'] == 'Pending') {
                // Fee selector
                $feeSelector = $form->getFactory()->createSelectFee('addNewFee', $gibbonSchoolYearID)->addClass('addBlock');

                // Block template
                $blockTemplate = $form->getFactory()->createTable()->setClass('blank');
                $row = $blockTemplate->addRow();
                    $row->addTextField('name')->setClass('standardWidth floatLeft noMargin title')->isRequired()->placeholder(__('Fee Name'))
                        ->append('<input type="hidden" id="gibbonFinanceFeeID" name="gibbonFinanceFeeID" value="">')
                        ->append('<input type="hidden" id="feeType" name="feeType" value="">');
                    
                $col = $blockTemplate->addRow()->addColumn()->addClass('inline');
                    $col->addSelectFeeCategory('gibbonFinanceFeeCategoryID')
                        ->setClass('shortWidth floatLeft noMargin');

                    $col->addCurrency('fee')
                        ->setClass('shortWidth floatLeft')
                        ->isRequired()
                        ->placeholder(__('Value').(!empty($_SESSION[$guid]['currency'])? ' ('.$_SESSION[$guid]['currency'].')' : ''));
                    
                $col = $blockTemplate->addRow()->addClass('showHide fullWidth')->addColumn();
                    $col->addLabel('description', __('Description'));
                    $col->addTextArea('description')->setRows('auto')->setClass('fullWidth floatNone noMargin');

                // Custom Blocks for Fees
                $row = $form->addRow();
                    $customBlocks = $row->addCustomBlocks('feesBlock', $gibbon->session)
                        ->fromTemplate($blockTemplate)
                        ->settings(array('inputNameStrategy' => 'string', 'addOnEvent' => 'change', 'sortable' => true))
                        ->placeholder(__('Fees will be listed here...'))
                        ->addToolInput($feeSelector)
                        ->addBlockButton('showHide', __('Show/Hide'), 'plus.png');

                // Add existing blocks
                while ($fee = $resultFees->fetch()) {
                    $fee['readonly'] = ($fee['feeType'] == 'Standard')? array('name', 'fee', 'description', 'gibbonFinanceFeeCategoryID') : array('name', 'fee', 'gibbonFinanceFeeCategoryID');
                    $fee['gibbonFinanceInvoiceFeeID'] = str_pad($fee['gibbonFinanceInvoiceFeeID'], 15, '0', STR_PAD_LEFT);
                    $fee['gibbonFinanceFeeCategoryID'] = str_pad($fee['gibbonFinanceFeeCategoryID'], 4, '0', STR_PAD_LEFT);

                    $customBlocks->addBlock($fee['gibbonFinanceInvoiceFeeID'], $fee);
                }

                // Add predefined block data (for templating new blocks, triggered with the feeSelector)
                $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
                $sql = "SELECT gibbonFinanceFeeID as groupBy, gibbonFinanceFeeID, name, description, fee, gibbonFinanceFeeCategoryID FROM gibbonFinanceFee ORDER BY name";
                $result = $pdo->executeQuery($data, $sql);
                $feeData = $result->rowCount() > 0? $result->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE) : array();

                $customBlocks->addPredefinedBlock('Ad Hoc Fee', array('feeType' => 'Ad Hoc', 'gibbonFinanceFeeID' => 0));
                foreach ($feeData as $gibbonFinanceFeeID => $data) {
                    $customBlocks->addPredefinedBlock($gibbonFinanceFeeID, $data + array('feeType' => 'Standard', 'readonly' => ['name', 'fee', 'description', 'gibbonFinanceFeeCategoryID']) );
                }
            } else {
                // Display fees already issued (readonly)
                if ($resultFees->rowCount() == 0) {
                    $form->addRow()->addAlert(__('There are no records to display.'), 'error');
                } else {
                    $table = $form->addRow()->addTable()->addClass('colorOddEven');

                    $header = $table->addHeaderRow();
                        $header->addContent(__('Name'));
                        $header->addContent(__('Category'));
                        $header->addContent(__('Description'));
                        $header->addContent(__('Fee'))->append(' <small><i>('.$_SESSION[$guid]['currency'].')</i></small>');

                    $feeTotal = 0;
                    while ($fee = $resultFees->fetch()) {
                        $feeTotal += $fee['fee'];
                        $row = $table->addRow();
                            $row->addContent($fee['name']);
                            $row->addContent($fee['category']);
                            $row->addContent($fee['description']);
                            $row->addContent(number_format($fee['fee'], 2, '.', ','))->prepend(substr($_SESSION[$guid]['currency'], 4).' ');
                    }

                    $row = $table->addRow()->addClass('current');
                        $row->addTableCell(__('Invoice Total:'))->colspan(3)->wrap('<b class="floatRight">', '</b>');
                        $row->addTableCell(number_format($feeTotal, 2, '.', ','))->prepend(substr($_SESSION[$guid]['currency'], 4).' ')->wrap('<b>', '</b>');
                }
            }

            $form->addRow()->addHeading(__('Payment Log'));

            $form->addRow()->addContent(getPaymentLog($connection2, $guid, 'gibbonFinanceInvoice', $gibbonFinanceInvoiceID));

            // EMAIL RECEIPTS
            if ($values['status'] == 'Issued' || $values['status'] == 'Paid - Partial') {
                $form->toggleVisibilityByClass('emailReceipts')->onSelect('status')->when(array('Paid', 'Paid - Partial', 'Paid - Complete'));
                $form->addRow()->addHeading(__('Email Receipt'))->addClass('emailReceipts');

                $row = $form->addRow()->addClass('emailReceipts');
                    $row->addYesNoRadio('emailReceipt')->checked('Y');

                $form->toggleVisibilityByClass('emailReceiptsTable')->onRadio('emailReceipt')->when(array('Y'));

                $email = getSettingByScope($connection2, 'Finance', 'email');
                $form->addHiddenValue('email', $email);
                if (empty($email)) {
                    $row = $form->addRow()->addClass('emailReceipts emailReceiptsTable');
                    $row->addAlert(__('An outgoing email address has not been set up under Invoice & Receipt Settings, and so no emails can be sent.'), 'error');
                } else {
                    $row = $form->addRow()->addClass('emailReceipts emailReceiptsTable');
                    $row->addInvoiceEmailCheckboxes('emails[]', 'names[]', $values, $gibbon->session);
                }
            }

            // EMAIL REMINDERS
            if ($values['status'] == 'Issued' && $values['invoiceDueDate'] < date('Y-m-d')) {

                $form->toggleVisibilityByClass('emailReminders')->onSelect('status')->when(array('Issued'));
                $form->addRow()->addHeading(sprintf(__('Email Reminder %1$s'), ($values['reminderCount'])+1))->addClass('emailReminders');

                $row = $form->addRow()->addClass('emailReminders');
                    $row->addYesNoRadio('emailReminder')->checked('Y');

                $form->toggleVisibilityByClass('emailRemindersTable')->onRadio('emailReminder')->when(array('Y'));

                $email = getSettingByScope($connection2, 'Finance', 'email');
                $form->addHiddenValue('email', $email);
                if (empty($email)) {
                    $row = $form->addRow()->addClass('emailReminders emailRemindersTable');
                    $row->addAlert(__('An outgoing email address has not been set up under Invoice & Receipt Settings, and so no emails can be sent.'), 'error');
                } else {
                    $row = $form->addRow()->addClass('emailReminders emailRemindersTable');
                    $row->addInvoiceEmailCheckboxes('emails[]', 'names[]', $values, $gibbon->session);
                }
            }

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();
        }
    }
}
