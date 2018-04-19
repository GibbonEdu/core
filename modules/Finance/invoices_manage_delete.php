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

use Gibbon\Forms\Prefab\DeleteForm;

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/invoices_manage_delete.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Check if school year specified
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    $gibbonFinanceInvoiceID = $_GET['gibbonFinanceInvoiceID'];
    $status = $_GET['status'];
    $gibbonFinanceInvoiceeID = $_GET['gibbonFinanceInvoiceeID'];
    $monthOfIssue = $_GET['monthOfIssue'];
    $gibbonFinanceBillingScheduleID = $_GET['gibbonFinanceBillingScheduleID'];
    $gibbonFinanceFeeCategoryID = $_GET['gibbonFinanceFeeCategoryID'];

    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/invoices_manage.php&gibbonFinanceInvoiceID=$gibbonFinanceInvoiceID&gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID&gibbonFinanceFeeCategoryID=$gibbonFinanceFeeCategoryID'>".__($guid, 'Manage Invoices')."</a> > </div><div class='trailEnd'>".__($guid, 'Delete Invoice').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    if ($gibbonFinanceInvoiceID == '' or $gibbonSchoolYearID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID);
            $sql = "SELECT * FROM gibbonFinanceInvoice WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID AND status='Pending'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The selected record does not exist, or you do not have access to it.');
            echo '</div>';
        } else {
            $form = DeleteForm::createForm($_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/invoices_manage_deleteProcess.php?gibbonFinanceInvoiceID=$gibbonFinanceInvoiceID&gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID&gibbonFinanceFeeCategoryID=$gibbonFinanceFeeCategoryID");
            echo $form->getOutput();
        }
    }
}
?>
