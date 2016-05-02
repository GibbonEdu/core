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

include '../../config.php';

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

if (isActionAccessible($guid, $connection2, '/modules/Finance/expenses_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $financeExpenseExportIDs = $_SESSION[$guid]['financeExpenseExportIDs'];
    $gibbonFinanceBudgetCycleID = $_GET['gibbonFinanceBudgetCycleID'];

    if ($financeExpenseExportIDs == '' or $gibbonFinanceBudgetCycleID == '') {
        echo "<div class='error'>";
        echo __($guid, 'List of invoices or budget cycle have not been specified, and so this export cannot be completed.');
        echo '</div>';
    } else {
        echo '<h1>';
        echo __($guid, 'Expense Export');
        echo '</h1>';

        try {
            $whereCount = 0;
            $whereSched = '(';
            $data = array();
            foreach ($financeExpenseExportIDs as $gibbonFinanceExpenseID) {
                $data['gibbonFinanceExpenseID'.$whereCount] = $gibbonFinanceExpenseID;
                $whereSched .= 'gibbonFinanceExpense.gibbonFinanceExpenseID=:gibbonFinanceExpenseID'.$whereCount.' OR ';
                ++$whereCount;
            }
            $whereSched = substr($whereSched, 0, -4).')';

            //SQL for billing schedule AND pending
            $sql = "SELECT gibbonFinanceExpense.*, gibbonFinanceBudget.name AS budget, gibbonFinanceBudgetCycle.name AS budgetCycle, preferredName, surname
			FROM gibbonFinanceExpense
			JOIN gibbonPerson ON (gibbonFinanceExpense.gibbonPersonIDCreator=gibbonPerson.gibbonPersonID)
			JOIN gibbonFinanceBudget ON (gibbonFinanceExpense.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID)
			JOIN gibbonFinanceBudgetCycle ON (gibbonFinanceExpense.gibbonFinanceBudgetCycleID=gibbonFinanceBudgetCycle.gibbonFinanceBudgetCycleID)
			WHERE $whereSched";
            $sql .= " ORDER BY FIELD(gibbonFinanceExpense.status, 'Requested','Approved','Rejected','Cancelled','Ordered','Paid'), timestampCreator, surname, preferredName";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo "<th style='width: 120px'>";
        echo __($guid, 'Expense Number');
        echo '</th>';
        echo "<th style='width: 120px'>";
        echo __($guid, 'Budget');
        echo '</th>';
        echo "<th style='width: 120px'>";
        echo __($guid, 'Budget Cycle');
        echo '</th>';
        echo "<th style='width: 120px'>";
        echo __($guid, 'Title');
        echo '</th>';
        echo "<th style='width: 120px'>";
        echo __($guid, 'Status');
        echo '</th>';
        echo "<th style='width: 100px'>";
        echo __($guid, 'Cost')." <span style='font-style: italic; font-size: 85%'>(".$_SESSION[$guid]['currency'].')</span>';
        echo '</th>';
        echo "<th style='width: 90px'>";
        echo __($guid, 'Staff');
        echo '</th>';
        echo "<th style='width: 100px'>";
        echo __($guid, 'Timestamp')." <span style='font-style: italic; font-size: 85%'>(".$_SESSION[$guid]['currency'].')</span>';
        echo '</th>';
        echo '</tr>';

        $count = 0;
        while ($row = $result->fetch()) {
            ++$count;
                //COLOR ROW BY STATUS!
                echo '<tr>';
            echo '<td>';
            echo $row['gibbonFinanceExpenseID'];
            echo '</td>';
            echo '<td>';
            echo $row['budget'];
            echo '</td>';
            echo '<td>';
            echo $row['budgetCycle'];
            echo '</td>';
            echo '<td>';
            echo $row['title'];
            echo '</td>';
            echo '<td>';
            echo $row['status'];
            echo '</td>';
            echo '<td>';
            echo number_format($row['cost'], 2, '.', ',');
            echo '</td>';
            echo '<td>';
            echo formatName('', $row['preferredName'], $row['surname'], 'Staff', true, true);
            echo '</td>';
            echo '<td>';
            echo $row['timestampCreator'];
            echo '</td>';
            echo '</tr>';
        }
        if ($count == 0) {
            echo '<tr>';
            echo '<td colspan=2>';
            echo __($guid, 'There are no records to display.');
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }

    $_SESSION[$guid]['financeExpenseExportIDs'] = null;
}
