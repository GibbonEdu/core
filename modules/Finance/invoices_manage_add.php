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
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/invoices_manage_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Finance/invoices_manage.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."'>".__($guid, 'Manage Invoices')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Fees & Invoices').'</div>';
    echo '</div>';

    $error3 = __($guid, 'Some aspects of your update failed, effecting the following areas:').'<ul>';
    if (!empty($_GET['studentFailCount'])) {
        $error3 .= '<li>'.$_GET['studentFailCount'].' '.__($guid, 'students encountered problems.').'</li>';
    }
    if (!empty($_GET['invoiceFailCount'])) {
        $error3 .= '<li>'.$_GET['invoiceFailCount'].' '.__($guid, 'invoices encountered problems.').'</li>';
    }
    if (!empty($_GET['invoiceFeeFailCount'])) {
        $error3 .= '<li>'.$_GET['invoiceFeeFailCount'].' '.__($guid, 'fee entires encountered problems.').'</li>';
    }
    $error3 .= '</ul>'.__($guid, 'It is recommended that you remove all pending invoices and try to recreate them.');

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, array('error3' => $error3));
    }

    echo '<p>';
    echo __($guid, 'Here you can add fees to one or more students. These fees will be added to an existing invoice or used to form a new invoice, depending on the specified billing schedule and other details.');
    echo '</p>';

    //Check if school year specified
    $gibbonSchoolYearID = isset($_GET['gibbonSchoolYearID'])? $_GET['gibbonSchoolYearID'] : '';
    $status = isset($_GET['status'])? $_GET['status'] : '';
    $gibbonFinanceInvoiceeID = isset($_GET['gibbonFinanceInvoiceeID'])? $_GET['gibbonFinanceInvoiceeID'] : '';
    $monthOfIssue = isset($_GET['monthOfIssue'])? $_GET['monthOfIssue'] : '';
    $gibbonFinanceBillingScheduleID = isset($_GET['gibbonFinanceBillingScheduleID'])? $_GET['gibbonFinanceBillingScheduleID'] : '';
    $gibbonFinanceFeeCategoryID = isset($_GET['gibbonFinanceFeeCategoryID'])? $_GET['gibbonFinanceFeeCategoryID'] : '';
    if ($gibbonSchoolYearID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        if ($status != '' or $gibbonFinanceInvoiceeID != '' or $monthOfIssue != '' or $gibbonFinanceBillingScheduleID != '') {
            echo "<div class='linkTop'>";
            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Finance/invoices_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID&gibbonFinanceFeeCategoryID=$gibbonFinanceFeeCategoryID'>".__($guid, 'Back to Search Results').'</a>';
            echo '</div>';
        }

        $linkParams = compact('gibbonSchoolYearID', 'status', 'gibbonFinanceInvoiceeID', 'monthOfIssue', 'gibbonFinanceBillingScheduleID', 'gibbonFinanceFeeCategoryID'); 
        
        $form = Form::create('invoice', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/invoices_manage_addProcess.php?'.http_build_query($linkParams));
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

        $data= array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT name AS schoolYear FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID";
        $result = $pdo->executeQuery($data, $sql);
        $schoolYearName = $result->rowCount() > 0? $result->fetchColumn(0) : '';

        $form->addRow()->addHeading(__('Basic Information'));

        $row = $form->addRow();
            $row->addLabel('schoolYear', __('School Year'));
            $row->addTextField('schoolYear')->isRequired()->readonly()->setValue($schoolYearName);

        $row = $form->addRow();
            $row->addLabel('gibbonFinanceInvoiceeIDs', __('Invoicees'))->append(sprintf(__('Visit %1$sManage Invoicees%2$s to automatically generate missing students.'), "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Finance/invoicees_manage.php'>", '</a>'));
            $row->addSelectFinanceInvoicee('gibbonFinanceInvoiceeIDs', $gibbonSchoolYearID)->isRequired()->selectMultiple();

        $scheduling = array('Scheduled' => __('Scheduled'), 'Ad Hoc' => __('Ad Hoc'));
        $row = $form->addRow();
            $row->addLabel('scheduling', __('Scheduling'))->description(__('When using scheduled, invoice due date is linked to and determined by the schedule.'));
            $row->addRadio('scheduling')->fromArray($scheduling)->isRequired()->inline()->checked('Scheduled');

        $form->toggleVisibilityByClass('schedulingScheduled')->onRadio('scheduling')->when('Scheduled');
        $form->toggleVisibilityByClass('schedulingAdHoc')->onRadio('scheduling')->when('Ad Hoc');

        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT gibbonFinanceBillingScheduleID as value, name FROM gibbonFinanceBillingSchedule WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name";
        $row = $form->addRow()->addClass('schedulingScheduled');
            $row->addLabel('gibbonFinanceBillingScheduleID', __('Billing Schedule'));
            $row->addSelect('gibbonFinanceBillingScheduleID')
                ->fromQuery($pdo, $sql, $data)
                ->isRequired()
                ->placeholder()
                ->selected($gibbonFinanceBillingScheduleID);

        $row = $form->addRow()->addClass('schedulingAdHoc');
            $row->addLabel('invoiceDueDate', __('Invoice Due Date'))->description(__('For fees added to existing invoice, specified date will override existing due date.'));
            $row->addDate('invoiceDueDate')->isRequired();

        $row = $form->addRow();
            $row->addLabel('notes', __('Notes'))->description(__('Notes will be displayed on the final invoice and receipt.'));
            $row->addTextArea('notes')->setRows(5);

        $form->addRow()->addHeading(__('Fees'));

        // Get fees and categories
        $sql = "SELECT gibbonFinanceFeeCategory.name as category, gibbonFinanceFee.gibbonFinanceFeeCategoryID, gibbonFinanceFee.gibbonFinanceFeeID, gibbonFinanceFee.name, gibbonFinanceFee.description, gibbonFinanceFee.fee 
                FROM gibbonFinanceFee 
                JOIN gibbonFinanceFeeCategory ON (gibbonFinanceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) 
                ORDER BY name";
        $result = $pdo->executeQuery(array(), $sql);

        $feeData = array();
        $feesByCategory = $result->rowCount() > 0? $result->fetchAll() : array();
        $feesByCategory = array_reduce($feesByCategory, function($group, $item) use (&$feeData) {
            $feeData[$item['gibbonFinanceFeeID']] = $item;
            $group[$item['category']][$item['gibbonFinanceFeeID']] = $item['name'];
            return $group;
        }, array());

        // Fee selector
        $feeSelector = $form->getFactory()->createSelect('addNewFee')
            ->addClass('addBlock')
            ->fromArray(array('' => __('Choose a fee to add it')))
            ->fromArray(array('Ad Hoc Fee' => __('Ad Hoc Fee')))
            ->fromArray($feesByCategory);

        // Block template
        $blockTemplate = $form->getFactory()->createTable()->setClass('blank');
            $row = $blockTemplate->addRow();
                $row->addTextField('name')->setClass('standardWidth floatLeft noMargin title')->isRequired()->placeholder(__('Fee Name'))
                    ->append('<input type="hidden" id="gibbonFinanceFeeID" name="gibbonFinanceFeeID" value="">')
                    ->append('<input type="hidden" id="feeType" name="feeType" value="">');
                
            $col = $blockTemplate->addRow()->addColumn()->addClass('inline');
                $sql = "SELECT gibbonFinanceFeeCategoryID as value, name FROM gibbonFinanceFeeCategory WHERE active='Y' AND NOT gibbonFinanceFeeCategoryID=1 ORDER BY name";
                $col->addSelect('gibbonFinanceFeeCategoryID')
                    ->fromArray(array('0001' => __('Other')))
                    ->fromQuery($pdo, $sql)
                    ->setClass('shortWidth floatLeft noMargin');

                $col->addCurrency('fee')
                    ->setClass('shortWidth floatLeft')
                    ->isRequired()
                    ->placeholder(__('Value').(!empty($_SESSION[$guid]['currency'])? ' ('.$_SESSION[$guid]['currency'].')' : ''));
                
            $col = $blockTemplate->addRow()->addClass('showHide displayNone fullWidth')->addColumn();
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

        // Add predefined block data (for templating new blocks, triggered with the feeSelector)
        $customBlocks->addPredefinedBlock('Ad Hoc Fee', array('feeType' => 'Ad Hoc', 'gibbonFinanceFeeID' => 0));
        foreach ($feeData as $gibbonFinanceFeeID => $data) {
            $customBlocks->addPredefinedBlock($gibbonFinanceFeeID, $data + array('feeType' => 'Standard', 'readonly' => ['name', 'fee', 'description', 'gibbonFinanceFeeCategoryID']) );
        }

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        echo $form->getOutput();
    }
}

