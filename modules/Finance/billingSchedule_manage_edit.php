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

@session_start();

if (isActionAccessible($guid, $connection2, '/modules/Finance/billingSchedule_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Finance/billingSchedule_manage.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."'>".__($guid, 'Manage Billing Schedule')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Entry').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    $gibbonFinanceBillingScheduleID = $_GET['gibbonFinanceBillingScheduleID'];
    $search = $_GET['search'];
    if ($gibbonFinanceBillingScheduleID == '' or $gibbonSchoolYearID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonFinanceBillingScheduleID' => $gibbonFinanceBillingScheduleID);
            $sql = 'SELECT * FROM gibbonFinanceBillingSchedule WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceBillingScheduleID=:gibbonFinanceBillingScheduleID';
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
            $resultRow = $result->fetch();

            if ($search != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Finance/billingSchedule_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search'>".__($guid, 'Back to Search Results').'</a>';
                echo '</div>';
            }

            $yearName = '';
            try {
                $dataYear = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
                $sqlYear = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
                $resultYear = $connection2->prepare($sqlYear);
                $resultYear->execute($dataYear);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($resultYear->rowCount() == 1) {
                $rowYear = $resultYear->fetch();
                $yearName = $rowYear['name'];
            }

            $form = Form::create("edit", $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/billingSchedule_manage_editProcess.php?gibbonSchoolYearID=$gibbonSchoolYearID&search=$search");

            $form->addHiddenValue("gibbonFinanceBillingScheduleID", $gibbonFinanceBillingScheduleID);
            $form->addHiddenValue("address", $_SESSION[$guid]['address']);

            $row = $form->addRow();
                $row->addLabel("yearName", __("School Year"))->description(__("This value cannot be changed."));
                $row->addTextField("yearName")->setValue($yearName)->readonly(true)->isRequired();

            $row = $form->addRow();
                $row->addLabel("name", __("Name"));
                $row->addTextField("name")->setValue(htmlprep($resultRow['name']))->maxLength(100)->isRequired();

            $row = $form->addRow();
                $row->addLabel("active", __("Active"));
                $row->addYesNo("active")->selected($resultRow['active'])->isRequired();

            $row = $form->addRow();
                $row->addLabel("description", __("Description"));
                $row->addTextArea("description")->setValue(htmlPrep($resultRow['description']))->setRows(5);

            $row = $form->addRow();
                $row->addLabel("invoiceIssueDate", __('Invoice Issue Date'))->description(__($guid, 'Intended issue date.').'<br/>')->append(__('Format:').' ')->append($_SESSION[$guid]['i18n']['dateFormat']);
                $row->addDate('invoiceIssueDate')->setValue(dateConvertBack($guid, $resultRow['invoiceIssueDate']))->isRequired();

            $row = $form->addRow();
                $row->addLabel('invoiceDueDate', __('Invoice Due Date'))->description(__($guid, 'Final payment date.').'<br/>')->append(__('Format:').' ')->append($_SESSION[$guid]['i18n']['dateFormat']);
                $row->addDate('invoiceDueDate')->setValue(dateConvertBack($guid, $resultRow['invoiceDueDate']))->isRequired();

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            print $form->getOutput();
        }
    }
}
