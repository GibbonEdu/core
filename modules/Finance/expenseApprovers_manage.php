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

if (isActionAccessible($guid, $connection2, '/modules/Finance/expenseApprovers_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Expense Approvers').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, array('success0' => 'Your request was completed successfully.'));
    }

    //Set pagination variable
    $page = 1;
    if (isset($_GET['page'])) {
        $page = $_GET['page'];
    }
    if ((!is_numeric($page)) or $page < 1) {
        $page = 1;
    }

    $expenseApprovalType = getSettingByScope($connection2, 'Finance', 'expenseApprovalType');
    $budgetLevelExpenseApproval = getSettingByScope($connection2, 'Finance', 'budgetLevelExpenseApproval');
    try {
        $data = array();
        if ($expenseApprovalType == 'Chain Of All') {
            $sql = "SELECT gibbonFinanceExpenseApprover.*, surname, preferredName FROM gibbonFinanceExpenseApprover JOIN gibbonPerson ON (gibbonFinanceExpenseApprover.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' ORDER BY sequenceNumber, surname, preferredName";
        } else {
            $sql = "SELECT gibbonFinanceExpenseApprover.*, surname, preferredName FROM gibbonFinanceExpenseApprover JOIN gibbonPerson ON (gibbonFinanceExpenseApprover.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' ORDER BY surname, preferredName";
        }
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    echo '<p>';
    if ($expenseApprovalType == 'One Of') {
        if ($budgetLevelExpenseApproval == 'Y') {
            echo __($guid, "Expense approval has been set as 'One Of', which means that only one of the people listed below (as well as someone with Full budget access) needs to approve an expense before it can go ahead.");
        } else {
            echo __($guid, "Expense approval has been set as 'One Of', which means that only one of the people listed below needs to approve an expense before it can go ahead.");
        }
    } elseif ($expenseApprovalType == 'Two Of') {
        if ($budgetLevelExpenseApproval == 'Y') {
            echo __($guid, "Expense approval has been set as 'Two Of', which means that only two of the people listed below (as well as someone with Full budget access) need to approve an expense before it can go ahead.");
        } else {
            echo __($guid, "Expense approval has been set as 'Two Of', which means that only two of the people listed below need to approve an expense before it can go ahead.");
        }
    } elseif ($expenseApprovalType == 'Chain Of All') {
        if ($budgetLevelExpenseApproval == 'Y') {
            echo __($guid, "Expense approval has been set as 'Chain Of All', which means that all of the people listed below (as well as someone with Full budget access) need to approve an expense, in order from lowest to highest, before it can go ahead.");
        } else {
            echo __($guid, "Expense approval has been set as 'Chain Of All', which means that all of the people listed below need to approve an expense, in order from lowest to highest, before it can go ahead.");
        }
    } else {
        echo __($guid, 'Expense Approval policies have not been set up: this should be done under Admin > School Admin > Manage Finance Settings.');
    }
    echo '</p>';

    echo "<div class='linkTop'>";
    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/expenseApprovers_manage_add.php'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
    echo '</div>';

    if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __($guid, 'Name');
        echo '</th>';
        if ($expenseApprovalType == 'Chain Of All') {
            echo '<th>';
            echo __($guid, 'Sequence Number');
            echo '</th>';
        }
        echo '<th>';
        echo __($guid, 'Actions');
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

            //COLOR ROW BY STATUS!
            echo "<tr class=$rowNum>";
            echo '<td>';
            echo formatName('', $row['preferredName'], $row['surname'], 'Staff', true, true);
            echo '</td>';
            if ($expenseApprovalType == 'Chain Of All') {
                echo '<td>';
                if ($row['sequenceNumber'] != '') {
                    echo __($guid, $row['sequenceNumber']);
                }
                echo '</td>';
            }
            echo '<td>';
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/expenseApprovers_manage_edit.php&gibbonFinanceExpenseApproverID='.$row['gibbonFinanceExpenseApproverID']."'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
            echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/expenseApprovers_manage_delete.php&gibbonFinanceExpenseApproverID='.$row['gibbonFinanceExpenseApproverID']."&width=650&height=135'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a> ";
            echo '</td>';
            echo '</tr>';

            ++$count;
        }
        echo '</table>';
    }
}
