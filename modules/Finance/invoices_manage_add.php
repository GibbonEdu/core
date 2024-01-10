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

use Gibbon\Http\Url;
use Gibbon\Forms\Form;
use Gibbon\Module\Finance\Forms\FinanceFormFactory;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/invoices_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
    $status = $_GET['status'] ?? '';
    $gibbonFinanceInvoiceeID = $_GET['gibbonFinanceInvoiceeID'] ?? '';
    $monthOfIssue = $_GET['monthOfIssue'] ?? '';
    $gibbonFinanceBillingScheduleID = $_GET['gibbonFinanceBillingScheduleID'] ?? '';
    $gibbonFinanceFeeCategoryID = $_GET['gibbonFinanceFeeCategoryID'] ?? '';

    $urlParams = compact('gibbonSchoolYearID', 'status', 'gibbonFinanceInvoiceeID', 'monthOfIssue', 'gibbonFinanceBillingScheduleID', 'gibbonFinanceFeeCategoryID');

    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage Invoices'), 'invoices_manage.php', $urlParams)
        ->add(__('Add Fees & Invoices'));

    $error3 = __('Some aspects of your update failed, effecting the following areas:').'<ul>';
    if (!empty($_GET['studentFailCount'])) {
        $error3 .= '<li>'.$_GET['studentFailCount'].' '.__('students encountered problems.').'</li>';
    }
    if (!empty($_GET['invoiceFailCount'])) {
        $error3 .= '<li>'.$_GET['invoiceFailCount'].' '.__('invoices encountered problems.').'</li>';
    }
    if (!empty($_GET['invoiceFeeFailCount'])) {
        $error3 .= '<li>'.$_GET['invoiceFeeFailCount'].' '.__('fee entries encountered problems.').'</li>';
    }
    $error3 .= '</ul>'.__('It is recommended that you remove all pending invoices and try to recreate them.');

    $page->return->addReturns(['error3' => $error3]);

    echo '<p>';
    echo __('Here you can add fees to one or more students. These fees will be added to an existing invoice or used to form a new invoice, depending on the specified billing schedule and other details.');
    echo '</p>';

    if ($gibbonSchoolYearID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        $data= array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT name AS schoolYear FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID";
        $result = $pdo->executeQuery($data, $sql);
        $schoolYearName = $result->rowCount() > 0? $result->fetchColumn(0) : '';

        if ($status != '' or $gibbonFinanceInvoiceeID != '' or $monthOfIssue != '' or $gibbonFinanceBillingScheduleID != '') {
            $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Finance', 'invoices_manage.php')->withQueryParams($urlParams));
        }

        $form = Form::create('invoice', $session->get('absoluteURL').'/modules/'.$session->get('module').'/invoices_manage_addProcess.php?'.http_build_query($urlParams));
        $form->setFactory(FinanceFormFactory::create($pdo));

        $form->addHiddenValue('address', $session->get('address'));

        $form->addRow()->addHeading('Basic Information', __('Basic Information'));

        $row = $form->addRow();
            $row->addLabel('schoolYear', __('School Year'));
            $row->addTextField('schoolYear')->required()->readonly()->setValue($schoolYearName);

        $row = $form->addRow();
            $row->addLabel('gibbonFinanceInvoiceeIDs', __('Invoicees'))->append(sprintf(__('Visit %1$sManage Invoicees%2$s to automatically generate missing students.'), "<a href='".$session->get('absoluteURL')."/index.php?q=/modules/Finance/invoicees_manage.php'>", '</a>'));
            $row->addSelectInvoicee('gibbonFinanceInvoiceeIDs', $gibbonSchoolYearID, ["byClass" => true])->required()->selectMultiple();

        $scheduling = array('Scheduled' => __('Scheduled'), 'Ad Hoc' => __('Ad Hoc'));
        $row = $form->addRow();
            $row->addLabel('scheduling', __('Scheduling'))->description(__('When using scheduled, invoice due date is linked to and determined by the schedule.'));
            $row->addRadio('scheduling')->fromArray($scheduling)->required()->inline()->checked('Scheduled');

        $form->toggleVisibilityByClass('schedulingScheduled')->onRadio('scheduling')->when('Scheduled');
        $form->toggleVisibilityByClass('schedulingAdHoc')->onRadio('scheduling')->when('Ad Hoc');

        $row = $form->addRow()->addClass('schedulingScheduled');
            $row->addLabel('gibbonFinanceBillingScheduleID', __('Billing Schedule'));
            $row->addSelectBillingSchedule('gibbonFinanceBillingScheduleID', $gibbonSchoolYearID)->required()->selected($gibbonFinanceBillingScheduleID);

        $row = $form->addRow()->addClass('schedulingAdHoc');
            $row->addLabel('invoiceDueDate', __('Invoice Due Date'))->description(__('For fees added to existing invoice, specified date will override existing due date.'));
            $row->addDate('invoiceDueDate')->required();

        $row = $form->addRow();
            $row->addLabel('notes', __('Notes'))->description(__('Notes will be displayed on the final invoice and receipt.'));
            $row->addTextArea('notes')->setRows(5);

        $form->addRow()->addHeading('Fees', __('Fees'));

        // CUSTOM BLOCKS

        // Fee selector
        $feeSelector = $form->getFactory()->createSelectFee('addNewFee', $gibbonSchoolYearID)->addClass('addBlock');

        // Block template
        $blockTemplate = $form->getFactory()->createTable()->setClass('blank');
            $row = $blockTemplate->addRow();
                $row->addTextField('name')->setClass('w-full pr-10 title')->required()->placeholder(__('Fee Name'))
                    ->append('<input type="hidden" id="gibbonFinanceFeeID" name="gibbonFinanceFeeID" value="">')
                    ->append('<input type="hidden" id="feeType" name="feeType" value="">');

            $col = $blockTemplate->addRow()->addColumn()->addClass('flex mt-1');
                $col->addSelectFeeCategory('gibbonFinanceFeeCategoryID')
                    ->setClass('w-48 m-0');

                $col->addCurrency('fee')
                    ->setClass('w-48 ml-1')
                    ->required()
                    ->placeholder(__('Value').(!empty($session->get('currency'))? ' ('.$session->get('currency').')' : ''));

            $col = $blockTemplate->addRow()->addClass('showHide w-full')->addColumn();
                $col->addLabel('description', __('Description'));
                $col->addTextArea('description')->setRows('auto')->setClass('w-full float-none m-0');

        // Custom Blocks for Fees
        $row = $form->addRow();
            $customBlocks = $row->addCustomBlocks('feesBlock', $session)
                ->fromTemplate($blockTemplate)
                ->settings(array('inputNameStrategy' => 'string', 'addOnEvent' => 'change', 'sortable' => true))
                ->placeholder(__('Fees will be listed here...'))
                ->addToolInput($feeSelector)
                ->addBlockButton('showHide', __('Show/Hide'), 'plus.png');

        // Add predefined block data (for templating new blocks, triggered with the feeSelector)
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT gibbonFinanceFeeID as groupBy, gibbonFinanceFeeID, name, description, fee, gibbonFinanceFeeCategoryID FROM gibbonFinanceFee WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name";
        $result = $pdo->executeQuery($data, $sql);
        $feeData = $result->rowCount() > 0? $result->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE) : array();

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
