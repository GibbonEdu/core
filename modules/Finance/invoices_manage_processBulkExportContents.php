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

if (isActionAccessible($guid, $connection2, '/modules/Finance/invoices_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $gibbonFinanceInvoiceIDs = $_SESSION[$guid]['financeInvoiceExportIDs'];
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];

    if ($gibbonFinanceInvoiceIDs == '' or $gibbonSchoolYearID == '') {
        echo "<div class='error'>";
        echo __($guid, 'List of invoices or school year have not been specified, and so this export cannot be completed.');
        echo '</div>';
    } else {
        echo '<h1>';
        echo __($guid, 'Invoice Export');
        echo '</h1>';

        try {
            $whereCount = 0;
            $whereSched = '(';
            $whereAdHoc = '(';
            $whereNotPending = '(';
            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
            foreach ($gibbonFinanceInvoiceIDs as $gibbonFinanceInvoiceID) {
                $data['gibbonFinanceInvoiceID'.$whereCount] = $gibbonFinanceInvoiceID;
                $whereSched .= 'gibbonFinanceInvoice.gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID'.$whereCount.' OR ';
                ++$whereCount;
            }
            $whereSched = substr($whereSched, 0, -4).')';

            //SQL for billing schedule AND pending
            $sql = "(SELECT gibbonFinanceInvoice.gibbonFinanceInvoiceID, surname, preferredName, gibbonPerson.gibbonPersonID, dob, gender, studentID, gibbonFinanceInvoice.invoiceTo, gibbonFinanceInvoice.status, gibbonFinanceInvoice.invoiceIssueDate, gibbonFinanceBillingSchedule.invoiceDueDate, paidDate, paidAmount, gibbonFinanceBillingSchedule.name AS billingSchedule, NULL AS billingScheduleExtra, notes, gibbonRollGroup.name AS rollGroup FROM gibbonFinanceInvoice JOIN gibbonFinanceBillingSchedule ON (gibbonFinanceInvoice.gibbonFinanceBillingScheduleID=gibbonFinanceBillingSchedule.gibbonFinanceBillingScheduleID) JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoice.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID) JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=gibbonFinanceInvoice.gibbonSchoolYearID) LEFT JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonFinanceInvoice.gibbonSchoolYearID=:gibbonSchoolYearID AND billingScheduleType='Scheduled' AND gibbonFinanceInvoice.status='Pending' AND $whereSched)";
            $sql .= ' UNION ';
            //SQL for Ad Hoc AND pending
            $sql .= "(SELECT gibbonFinanceInvoice.gibbonFinanceInvoiceID, surname, preferredName, gibbonPerson.gibbonPersonID, dob, gender, studentID, gibbonFinanceInvoice.invoiceTo, gibbonFinanceInvoice.status, invoiceIssueDate, invoiceDueDate, paidDate, paidAmount, 'Ad Hoc' AS billingSchedule, NULL AS billingScheduleExtra, notes, gibbonRollGroup.name AS rollGroup FROM gibbonFinanceInvoice JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoice.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID) JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=gibbonFinanceInvoice.gibbonSchoolYearID) LEFT JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonFinanceInvoice.gibbonSchoolYearID=:gibbonSchoolYearID AND billingScheduleType='Ad Hoc' AND gibbonFinanceInvoice.status='Pending' AND $whereSched)";
            $sql .= ' UNION ';
            //SQL for NOT Pending
            $sql .= "(SELECT gibbonFinanceInvoice.gibbonFinanceInvoiceID, surname, preferredName, gibbonPerson.gibbonPersonID, dob, gender, studentID, gibbonFinanceInvoice.invoiceTo, gibbonFinanceInvoice.status, gibbonFinanceInvoice.invoiceIssueDate, gibbonFinanceInvoice.invoiceDueDate, paidDate, paidAmount, billingScheduleType AS billingSchedule, gibbonFinanceBillingSchedule.name AS billingScheduleExtra, notes, gibbonRollGroup.name AS rollGroup FROM gibbonFinanceInvoice LEFT JOIN gibbonFinanceBillingSchedule ON (gibbonFinanceInvoice.gibbonFinanceBillingScheduleID=gibbonFinanceBillingSchedule.gibbonFinanceBillingScheduleID) JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoice.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID) JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=gibbonFinanceInvoice.gibbonSchoolYearID) LEFT JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonFinanceInvoice.gibbonSchoolYearID=:gibbonSchoolYearID AND NOT gibbonFinanceInvoice.status='Pending' AND $whereSched)";
            $sql .= " ORDER BY FIND_IN_SET(status, 'Pending,Issued,Paid,Refunded,Cancelled'), invoiceIssueDate, surname, preferredName";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo "<th style='width: 120px'>";
        echo __($guid, 'Invoice Number');
        echo '</th>';
        echo "<th style='width: 120px'>";
        echo __($guid, 'Student');
        echo '</th>';
        echo "<th style='width: 120px'>";
        echo __($guid, 'Roll Group');
        echo '</th>';
        echo "<th style='width: 120px'>";
        echo __($guid, 'Invoice To');
        echo '</th>';
        echo "<th style='width: 120px'>";
        echo __($guid, 'DOB');
        echo '</th>';
        echo "<th style='width: 120px'>";
        echo __($guid, 'Gender');
        echo '</th>';
        echo "<th style='width: 100px'>";
        echo __($guid, 'Status');
        echo '</th>';
        echo "<th style='width: 90px'>";
        echo __($guid, 'Schedule');
        echo '</th>';
        echo "<th style='width: 100px'>";
        echo __($guid, 'Total Value')." <span style='font-style: italic; font-size: 85%'>(".$_SESSION[$guid]['currency'].')</span>';
        echo '</th>';
        echo "<th style='width: 80px'>";
        echo __($guid, 'Issue Date');
        echo '</th>';
        echo "<th style='width: 80px'>";
        echo __($guid, 'Due Date');
        echo '</th>';
        echo "<th style='width: 80px'>";
        echo __($guid, 'Date Paid');
        echo '</th>';
        echo "<th style='width: 100px'>";
        echo __($guid, 'Amount Paid')." <span style='font-style: italic; font-size: 85%'>(".$_SESSION[$guid]['currency'].')</span>';
        echo '</th>';

        echo '</tr>';

        $count = 0;
        while ($row = $result->fetch()) {
            ++$count;
                //COLOR ROW BY STATUS!
                echo '<tr>';
            echo '<td>';
            $invoiceNumber = getSettingByScope($connection2, 'Finance', 'invoiceNumber');
            if ($invoiceNumber == 'Person ID + Invoice ID') {
                echo ltrim($row['gibbonPersonID'], '0').'-'.ltrim($row['gibbonFinanceInvoiceID'], '0');
            } elseif ($invoiceNumber == 'Student ID + Invoice ID') {
                echo ltrim($row['studentID'], '0').'-'.ltrim($row['gibbonFinanceInvoiceID'], '0');
            } else {
                echo ltrim($row['gibbonFinanceInvoiceID'], '0');
            }
            echo '</td>';
            echo '<td>';
            echo formatName('', htmlPrep($row['preferredName']), htmlPrep($row['surname']), 'Student', true);
            echo '</td>';
            echo '<td>';
            echo $row['rollGroup'];
            echo '</td>';
            echo '<td>';
            echo $row['invoiceTo'];
            echo '</td>';
            echo '<td>';
            if ($row['dob'] != '') {
                echo dateConvertBack($guid, $row['dob']);
            }
            echo '</td>';
            echo '<td>';
            echo $row['gender'];
            echo '</td>';
            echo '<td>';
            echo $row['status'];
            echo '</td>';
            echo '<td>';
            if ($row['billingScheduleExtra'] != '') {
                echo $row['billingScheduleExtra'];
            } else {
                echo $row['billingSchedule'];
            }
            echo '</td>';
            echo '<td>';
                        //Calculate total value
                        $totalFee = 0;
            $feeError = false;
            try {
                $dataTotal = array('gibbonFinanceInvoiceID' => $row['gibbonFinanceInvoiceID']);
                if ($row['status'] == 'Pending') {
                    $sqlTotal = 'SELECT gibbonFinanceInvoiceFee.fee AS fee, gibbonFinanceFee.fee AS fee2 FROM gibbonFinanceInvoiceFee LEFT JOIN gibbonFinanceFee ON (gibbonFinanceInvoiceFee.gibbonFinanceFeeID=gibbonFinanceFee.gibbonFinanceFeeID) WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID';
                } else {
                    $sqlTotal = 'SELECT gibbonFinanceInvoiceFee.fee AS fee, NULL AS fee2 FROM gibbonFinanceInvoiceFee WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID';
                }
                $resultTotal = $connection2->prepare($sqlTotal);
                $resultTotal->execute($dataTotal);
            } catch (PDOException $e) {
                echo $e->getMessage();
                echo '<i>Error calculating total</i>';
                $feeError = true;
            }
            while ($rowTotal = $resultTotal->fetch()) {
                if (is_numeric($rowTotal['fee2'])) {
                    $totalFee += $rowTotal['fee2'];
                } else {
                    $totalFee += $rowTotal['fee'];
                }
            }
            if ($feeError == false) {
                if (substr($_SESSION[$guid]['currency'], 4) != '') {
                    echo substr($_SESSION[$guid]['currency'], 4).' ';
                }
                echo number_format($totalFee, 2, '.', ',');
            }
            echo '</td>';
            echo '<td>';
            echo dateConvertBack($guid, $row['invoiceIssueDate']);
            echo '</td>';
            echo '<td>';
            echo dateConvertBack($guid, $row['invoiceDueDate']);
            echo '</td>';
            echo '<td>';
            if ($row['paidDate'] != '') {
                echo dateConvertBack($guid, $row['paidDate']);
            }
            echo '</td>';
            echo '<td>';
            echo number_format($row['paidAmount'], 2, '.', ',');
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

    $_SESSION[$guid]['financeInvoiceExportIDs'] = null;
}
