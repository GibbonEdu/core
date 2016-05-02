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

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/User Admin/data_finance_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/User Admin/data_finance.php'>".__($guid, 'Finance Data Updates')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Request').'</div>';
    echo '</div>';

    //Check if school year specified
    $gibbonFinanceInvoiceeUpdateID = $_GET['gibbonFinanceInvoiceeUpdateID'];
    if ($gibbonFinanceInvoiceeUpdateID == 'Y') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonFinanceInvoiceeUpdateID' => $gibbonFinanceInvoiceeUpdateID);
            $sql = 'SELECT gibbonFinanceInvoiceeUpdate.gibbonFinanceInvoiceeID, gibbonFinanceInvoicee.invoiceTo AS invoiceTo, gibbonFinanceInvoicee.companyName AS companyName, gibbonFinanceInvoicee.companyContact AS companyContact, gibbonFinanceInvoicee.companyAddress AS companyAddress, gibbonFinanceInvoicee.companyEmail AS companyEmail, gibbonFinanceInvoicee.companyCCFamily AS companyCCFamily, gibbonFinanceInvoicee.companyPhone AS companyPhone, gibbonFinanceInvoicee.companyAll AS companyAll, gibbonFinanceInvoicee.gibbonFinanceFeeCategoryIDList AS gibbonFinanceFeeCategoryIDList, gibbonFinanceInvoiceeUpdate.invoiceTo AS newinvoiceTo, gibbonFinanceInvoiceeUpdate.companyName AS newcompanyName, gibbonFinanceInvoiceeUpdate.companyContact AS newcompanyContact, gibbonFinanceInvoiceeUpdate.companyAddress AS newcompanyAddress, gibbonFinanceInvoiceeUpdate.companyEmail AS newcompanyEmail, gibbonFinanceInvoiceeUpdate.companyCCFamily AS newcompanyCCFamily, gibbonFinanceInvoiceeUpdate.companyPhone AS newcompanyPhone, gibbonFinanceInvoiceeUpdate.companyAll AS newcompanyAll, gibbonFinanceInvoiceeUpdate.gibbonFinanceFeeCategoryIDList AS newgibbonFinanceFeeCategoryIDList FROM gibbonFinanceInvoiceeUpdate JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoiceeUpdate.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID) WHERE gibbonFinanceInvoiceeUpdateID=:gibbonFinanceInvoiceeUpdateID';
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
            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            //Let's go!
            $row = $result->fetch();
            ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/data_finance_editProcess.php?gibbonFinanceInvoiceeUpdateID=$gibbonFinanceInvoiceeUpdateID" ?>">
				<?php
                echo "<table cellspacing='0' style='width: 100%'>";
            echo "<tr class='head'>";
            echo '<th>';
            echo __($guid, 'Field');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Current Value');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'New Value');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Accept');
            echo '</th>';
            echo '</tr>';

            $rowNum = 'even';

                    //COLOR ROW BY STATUS!
                    echo "<tr class='odd'>";
            echo '<td>';
            echo __($guid, 'Invoice To');
            echo '</td>';
            echo '<td>';
            echo $row['invoiceTo'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['invoiceTo'] != $row['newinvoiceTo']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newinvoiceTo'];
            echo '</td>';
            echo '<td>';
            if ($row['invoiceTo'] != $row['newinvoiceTo']) {
                echo "<input checked type='checkbox' name='newinvoiceToOn'><input name='newinvoiceTo' type='hidden' value='".htmlprep($row['newinvoiceTo'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo "<tr class='even'>";
            echo '<td>';
            echo __($guid, 'Company Name');
            echo '</td>';
            echo '<td>';
            echo $row['companyName'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['companyName'] != $row['newcompanyName']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newcompanyName'];
            echo '</td>';
            echo '<td>';
            if ($row['companyName'] != $row['newcompanyName']) {
                echo "<input checked type='checkbox' name='newcompanyNameOn'><input name='newcompanyName' type='hidden' value='".htmlprep($row['newcompanyName'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo "<tr class='odd'>";
            echo '<td>';
            echo __($guid, 'Company Contact Person');
            echo '</td>';
            echo '<td>';
            echo $row['companyContact'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['companyContact'] != $row['newcompanyContact']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newcompanyContact'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['companyContact'] != $row['newcompanyContact']) {
                echo "<input checked type='checkbox' name='newcompanyContactOn'><input name='newcompanyContact' type='hidden' value='".htmlprep($row['newcompanyContact'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo "<tr class='even'>";
            echo '<td>';
            echo __($guid, 'Company Address');
            echo '</td>';
            echo '<td>';
            echo $row['companyAddress'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['companyAddress'] != $row['newcompanyAddress']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newcompanyAddress'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['companyAddress'] != $row['newcompanyAddress']) {
                echo "<input checked type='checkbox' name='newcompanyAddressOn'><input name='newcompanyAddress' type='hidden' value='".htmlprep($row['newcompanyAddress'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo "<tr class='odd'>";
            echo '<td>';
            echo __($guid, 'Company Email');
            echo '</td>';
            echo '<td>';
            echo $row['companyEmail'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['companyEmail'] != $row['newcompanyEmail']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newcompanyEmail'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['companyEmail'] != $row['newcompanyEmail']) {
                echo "<input checked type='checkbox' name='newcompanyEmailOn'><input name='newcompanyEmail' type='hidden' value='".htmlprep($row['newcompanyEmail'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo "<tr class='even'>";
            echo '<td>';
            echo __($guid, 'CC Family?');
            echo '</td>';
            echo '<td>';
            echo $row['companyCCFamily'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['companyCCFamily'] != $row['newcompanyCCFamily']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newcompanyCCFamily'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['companyCCFamily'] != $row['newcompanyCCFamily']) {
                echo "<input checked type='checkbox' name='newcompanyCCFamilyOn'><input name='newcompanyCCFamily' type='hidden' value='".htmlprep($row['newcompanyCCFamily'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo "<tr class='odd'>";
            echo '<td>';
            echo __($guid, 'Company Phone');
            echo '</td>';
            echo '<td>';
            echo $row['companyPhone'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['companyPhone'] != $row['newcompanyPhone']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newcompanyPhone'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['companyPhone'] != $row['newcompanyPhone']) {
                echo "<input checked type='checkbox' name='newcompanyPhoneOn'><input name='newcompanyPhone' type='hidden' value='".htmlprep($row['newcompanyPhone'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo "<tr class='even'>";
            echo '<td>';
            echo __($guid, 'Company All?');
            echo '</td>';
            echo '<td>';
            echo $row['companyAll'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['companyAll'] != $row['newcompanyAll']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newcompanyAll'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['companyAll'] != $row['newcompanyAll']) {
                echo "<input checked type='checkbox' name='newcompanyAllOn'><input name='newcompanyAll' type='hidden' value='".htmlprep($row['newcompanyAll'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo "<tr class='odd'>";
            echo '<td>';
            echo __($guid, 'Company Fee Categories');
            echo '</td>';
            echo '<td>';
            echo $row['gibbonFinanceFeeCategoryIDList'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['gibbonFinanceFeeCategoryIDList'] != $row['newgibbonFinanceFeeCategoryIDList']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newgibbonFinanceFeeCategoryIDList'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['gibbonFinanceFeeCategoryIDList'] != $row['newgibbonFinanceFeeCategoryIDList']) {
                echo "<input checked type='checkbox' name='newgibbonFinanceFeeCategoryIDListOn'><input name='newgibbonFinanceFeeCategoryIDList' type='hidden' value='".htmlprep($row['newgibbonFinanceFeeCategoryIDList'])."'>";
            }
            echo '</td>';
            echo '</tr>';

            echo '<tr>';
            echo "<td class='right' colspan=4>";
            echo "<input name='gibbonFinanceInvoiceeID' type='hidden' value='".$row['gibbonFinanceInvoiceeID']."'>";
            echo "<input name='address' type='hidden' value='".$_GET['q']."'>";
            echo "<input type='submit' value='Submit'>";
            echo '</td>';
            echo '</tr>';
            echo '</table>';
            ?>
			</form>
			<?php

        }
    }
}
?>