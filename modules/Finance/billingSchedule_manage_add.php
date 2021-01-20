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

if (isActionAccessible($guid, $connection2, '/modules/Finance/billingSchedule_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];

    $urlParams = compact('gibbonSchoolYearID');
    
    $page->breadcrumbs
        ->add(__('Manage Billing Schedule'), 'billingSchedule_manage.php', $urlParams)
        ->add(__('Add Entry'));

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Finance/billingSchedule_manage_edit.php&gibbonFinanceBillingScheduleID='.$_GET['editID'].'&search='.$_GET['search'].'&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID'];
    }

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    //Check if school year specified
    $search = $_GET['search'];
    if ($gibbonSchoolYearID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        if ($search != '') {
            echo "<div class='linkTop'>";
            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Finance/billingSchedule_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search'>".__('Back to Search Results').'</a>';
            echo '</div>';
        }

        $form = Form::create("scheduleManageAdd", $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/billingSchedule_manage_addProcess.php?gibbonSchoolYearID=$gibbonSchoolYearID&search=$search");

        $form->addHiddenValue("address", $_SESSION[$guid]['address']);

        $row = $form->addRow();
        	$row->addLabel("yearName", __("School Year"));
        	$row->addTextField("yearName")->setValue($_SESSION[$guid]['gibbonSchoolYearName'])->readonly(true)->required();

        $row = $form->addRow();
        	$row->addLabel("name", __("Name"));
        	$row->addTextField("name")->maxLength(100)->required();

        $row = $form->addRow();
        	$row->addLabel("active", __("Active"));
        	$row->addYesNo("active")->required();

        $row = $form->addRow();
        	$row->addLabel("description", __("Description"));
        	$row->addTextArea("description")->setRows(5);

        $row = $form->addRow();
        	$row->addLabel("invoiceIssueDate", __('Invoice Issue Date'))->description(__('Intended issue date.').'<br/>')->append(__('Format:').' ')->append($_SESSION[$guid]['i18n']['dateFormat']);
        	$row->addDate('invoiceIssueDate')->required();

        $row = $form->addRow();
			$row->addLabel('invoiceDueDate', __('Invoice Due Date'))->description(__('Final payment date.').'<br/>')->append(__('Format:').' ')->append($_SESSION[$guid]['i18n']['dateFormat']);
			$row->addDate('invoiceDueDate')->required();

        $row = $form->addRow();
        	$row->addFooter();
        	$row->addSubmit();

        echo $form->getOutput();
    }
}
?>
