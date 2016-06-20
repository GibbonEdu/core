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
		$sql = "(SELECT gibbonFinanceInvoice.gibbonFinanceInvoiceID, surname, preferredName, gibbonPerson.gibbonPersonID, dob, gender,
				studentID, gibbonFinanceInvoice.invoiceTo, gibbonFinanceInvoice.status, gibbonFinanceInvoice.invoiceIssueDate,
				gibbonFinanceBillingSchedule.invoiceDueDate, paidDate, paidAmount, gibbonFinanceBillingSchedule.name AS billingSchedule,
				NULL AS billingScheduleExtra, notes, gibbonRollGroup.name AS rollGroup
			FROM gibbonFinanceInvoice
				JOIN gibbonFinanceBillingSchedule ON (gibbonFinanceInvoice.gibbonFinanceBillingScheduleID=gibbonFinanceBillingSchedule.gibbonFinanceBillingScheduleID)
				JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoice.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID)
				JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID)
				LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID
					AND gibbonStudentEnrolment.gibbonSchoolYearID=gibbonFinanceInvoice.gibbonSchoolYearID)
				LEFT JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
			WHERE gibbonFinanceInvoice.gibbonSchoolYearID=:gibbonSchoolYearID
				AND billingScheduleType='Scheduled'
				AND gibbonFinanceInvoice.status='Pending'
				AND $whereSched)";
		$sql .= ' UNION ';
		//SQL for Ad Hoc AND pending
		$sql .= "(SELECT gibbonFinanceInvoice.gibbonFinanceInvoiceID, surname, preferredName, gibbonPerson.gibbonPersonID, dob, gender, studentID, gibbonFinanceInvoice.invoiceTo, gibbonFinanceInvoice.status, invoiceIssueDate, invoiceDueDate, paidDate, paidAmount, 'Ad Hoc' AS billingSchedule, NULL AS billingScheduleExtra, notes, gibbonRollGroup.name AS rollGroup FROM gibbonFinanceInvoice JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoice.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID) JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=gibbonFinanceInvoice.gibbonSchoolYearID) LEFT JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonFinanceInvoice.gibbonSchoolYearID=:gibbonSchoolYearID AND billingScheduleType='Ad Hoc' AND gibbonFinanceInvoice.status='Pending' AND $whereSched)";
		$sql .= ' UNION ';
		//SQL for NOT Pending
		$sql .= "(SELECT gibbonFinanceInvoice.gibbonFinanceInvoiceID, surname, preferredName, gibbonPerson.gibbonPersonID, dob, gender, studentID, gibbonFinanceInvoice.invoiceTo, gibbonFinanceInvoice.status, gibbonFinanceInvoice.invoiceIssueDate, gibbonFinanceInvoice.invoiceDueDate, paidDate, paidAmount, billingScheduleType AS billingSchedule, gibbonFinanceBillingSchedule.name AS billingScheduleExtra, notes, gibbonRollGroup.name AS rollGroup FROM gibbonFinanceInvoice LEFT JOIN gibbonFinanceBillingSchedule ON (gibbonFinanceInvoice.gibbonFinanceBillingScheduleID=gibbonFinanceBillingSchedule.gibbonFinanceBillingScheduleID) JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoice.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID) JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=gibbonFinanceInvoice.gibbonSchoolYearID) LEFT JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonFinanceInvoice.gibbonSchoolYearID=:gibbonSchoolYearID AND NOT gibbonFinanceInvoice.status='Pending' AND $whereSched)";
		$sql .= " ORDER BY FIND_IN_SET(status, 'Pending,Issued,Paid,Refunded,Cancelled'), invoiceIssueDate, surname, preferredName";
		if (is_null($result = $pdo->executeQuery($data, $sql))) {
			echo "<div class='error'>".$pdo->getError().'</div>';
		}

		$excel = new Gibbon\Excel('invoices.xlsx');
		if ($excel->estimateCellCount($pdo) > 8000)    //  If too big, then render csv instead.
			return Gibbon\csv::generate($pdo, 'Invoices');
		$excel->setActiveSheetIndex(0);
		$excel->getProperties()->setTitle('Invoices');
		$excel->getProperties()->setSubject('Invoice Export');
		$excel->getProperties()->setDescription('Invoice Export');

        //Create border and fill style
        $style_border = array('borders' => array('right' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => '766f6e')), 'left' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => '766f6e')), 'top' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => '766f6e')), 'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => '766f6e'))));
        $style_head_fill = array('fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => 'B89FE2')));

        //Auto set column widths
        for($col = 'A'; $col !== 'I'; $col++)
            $excel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);

		$excel->getActiveSheet()->setCellValueByColumnAndRow(0, 1, __($guid, "Invoice Number"));
        $excel->getActiveSheet()->getStyleByColumnAndRow(0, 1)->applyFromArray($style_border);
        $excel->getActiveSheet()->getStyleByColumnAndRow(0, 1)->applyFromArray($style_head_fill);
		$excel->getActiveSheet()->setCellValueByColumnAndRow(1, 1, __($guid, "Student"));
        $excel->getActiveSheet()->getStyleByColumnAndRow(1, 1)->applyFromArray($style_border);
        $excel->getActiveSheet()->getStyleByColumnAndRow(1, 1)->applyFromArray($style_head_fill);
		$excel->getActiveSheet()->setCellValueByColumnAndRow(2, 1, __($guid, "Roll Group"));
        $excel->getActiveSheet()->getStyleByColumnAndRow(2, 1)->applyFromArray($style_border);
        $excel->getActiveSheet()->getStyleByColumnAndRow(2, 1)->applyFromArray($style_head_fill);
		$excel->getActiveSheet()->setCellValueByColumnAndRow(3, 1, __($guid, "Invoice To"));
        $excel->getActiveSheet()->getStyleByColumnAndRow(3, 1)->applyFromArray($style_border);
        $excel->getActiveSheet()->getStyleByColumnAndRow(3, 1)->applyFromArray($style_head_fill);
		$excel->getActiveSheet()->setCellValueByColumnAndRow(4, 1, __($guid, "DOB"));
        $excel->getActiveSheet()->getStyleByColumnAndRow(4, 1)->applyFromArray($style_border);
        $excel->getActiveSheet()->getStyleByColumnAndRow(4, 1)->applyFromArray($style_head_fill);
		$excel->getActiveSheet()->setCellValueByColumnAndRow(5, 1, __($guid, "Gender"));
        $excel->getActiveSheet()->getStyleByColumnAndRow(5, 1)->applyFromArray($style_border);
        $excel->getActiveSheet()->getStyleByColumnAndRow(5, 1)->applyFromArray($style_head_fill);
		$excel->getActiveSheet()->setCellValueByColumnAndRow(6, 1, __($guid, "Status"));
        $excel->getActiveSheet()->getStyleByColumnAndRow(6, 1)->applyFromArray($style_border);
        $excel->getActiveSheet()->getStyleByColumnAndRow(6, 1)->applyFromArray($style_head_fill);
		$excel->getActiveSheet()->setCellValueByColumnAndRow(7, 1, __($guid, "Schedule"));
        $excel->getActiveSheet()->getStyleByColumnAndRow(7, 1)->applyFromArray($style_border);
        $excel->getActiveSheet()->getStyleByColumnAndRow(7, 1)->applyFromArray($style_head_fill);
		$excel->getActiveSheet()->setCellValueByColumnAndRow(8, 1, __($guid, "Total Value") . '(' . $_SESSION[$guid]["currency"] .')');
        $excel->getActiveSheet()->getStyleByColumnAndRow(8, 1)->applyFromArray($style_border);
        $excel->getActiveSheet()->getStyleByColumnAndRow(8, 1)->applyFromArray($style_head_fill);
		$excel->getActiveSheet()->setCellValueByColumnAndRow(9, 1, __($guid, "Issue Date"));
        $excel->getActiveSheet()->getStyleByColumnAndRow(9, 1)->applyFromArray($style_border);
        $excel->getActiveSheet()->getStyleByColumnAndRow(9, 1)->applyFromArray($style_head_fill);
		$excel->getActiveSheet()->setCellValueByColumnAndRow(10, 1, __($guid, "Due Date"));
        $excel->getActiveSheet()->getStyleByColumnAndRow(10, 1)->applyFromArray($style_border);
        $excel->getActiveSheet()->getStyleByColumnAndRow(10, 1)->applyFromArray($style_head_fill);
		$excel->getActiveSheet()->setCellValueByColumnAndRow(11, 1, __($guid, "Date Paid"));
        $excel->getActiveSheet()->getStyleByColumnAndRow(11, 1)->applyFromArray($style_border);
        $excel->getActiveSheet()->getStyleByColumnAndRow(11, 1)->applyFromArray($style_head_fill);
		$excel->getActiveSheet()->setCellValueByColumnAndRow(12, 1, __($guid, "Amount Paid") . " (" . $_SESSION[$guid]["currency"] . ")" );
        $excel->getActiveSheet()->getStyleByColumnAndRow(12, 1)->applyFromArray($style_border);
        $excel->getActiveSheet()->getStyleByColumnAndRow(12, 1)->applyFromArray($style_head_fill);
		$excel->getActiveSheet()->getStyle("1:1")->getFont()->setBold(true);

		$r = 2;
		$count = 0;

		while ($row=$result->fetch()) {
			$count++ ;
			//Column A
			$invoiceNumber=getSettingByScope( $connection2, "Finance", "invoiceNumber" ) ;
			if ($invoiceNumber=="Person ID + Invoice ID") {
				$excel->getActiveSheet()->setCellValueByColumnAndRow(0, $r, ltrim($row["gibbonPersonID"],"0") . "-" . ltrim($row["gibbonFinanceInvoiceID"], "0"));
			}
			else if ($invoiceNumber=="Student ID + Invoice ID") {
				$excel->getActiveSheet()->setCellValueByColumnAndRow(0, $r, ltrim($row["studentID"],"0") . "-" . ltrim($row["gibbonFinanceInvoiceID"], "0"));
			}
			else {
				$excel->getActiveSheet()->setCellValueByColumnAndRow(0, $r, ltrim($row["gibbonFinanceInvoiceID"], "0"));
			}
            $excel->getActiveSheet()->getStyleByColumnAndRow(0, $r)->applyFromArray($style_border);
			//Column B
			$excel->getActiveSheet()->setCellValueByColumnAndRow(1, $r, formatName("", htmlPrep($row["preferredName"]), htmlPrep($row["surname"]), "Student", true));
            $excel->getActiveSheet()->getStyleByColumnAndRow(1, $r)->applyFromArray($style_border);
			//Column C
			$excel->getActiveSheet()->setCellValueByColumnAndRow(2, $r, $row["rollGroup"]);
            $excel->getActiveSheet()->getStyleByColumnAndRow(2, $r)->applyFromArray($style_border);
			//Column D
			$excel->getActiveSheet()->setCellValueByColumnAndRow(3, $r, $row["invoiceTo"]);
            $excel->getActiveSheet()->getStyleByColumnAndRow(3, $r)->applyFromArray($style_border);
			//Column E
            $excel->getActiveSheet()->setCellValueByColumnAndRow(4, $r, dateConvertBack($guid, $row["dob"]));
            $excel->getActiveSheet()->getStyleByColumnAndRow(4, $r)->applyFromArray($style_border);
			//Column F
			$excel->getActiveSheet()->setCellValueByColumnAndRow(5, $r, $row["gender"]);
            $excel->getActiveSheet()->getStyleByColumnAndRow(5, $r)->applyFromArray($style_border);
			//Column G
			$excel->getActiveSheet()->setCellValueByColumnAndRow(6, $r, $row["status"]);
            $excel->getActiveSheet()->getStyleByColumnAndRow(6, $r)->applyFromArray($style_border);
			//Column H
			if ($row["billingScheduleExtra"]!="")  {
				$excel->getActiveSheet()->setCellValueByColumnAndRow(7, $r, $row["billingScheduleExtra"]);
			}
			else {
				$excel->getActiveSheet()->setCellValueByColumnAndRow(7, $r, $row["billingSchedule"]);
			}
            $excel->getActiveSheet()->getStyleByColumnAndRow(7, $r)->applyFromArray($style_border);
			//Column I
			//Calculate total value
			$totalFee=0 ;
			$feeError = false ;
			$dataTotal=array("gibbonFinanceInvoiceID"=>$row["gibbonFinanceInvoiceID"]);
			if ($row["status"]=="Pending") {
				$sqlTotal="SELECT gibbonFinanceInvoiceFee.fee AS fee, gibbonFinanceFee.fee AS fee2
					FROM gibbonFinanceInvoiceFee
						LEFT JOIN gibbonFinanceFee ON (gibbonFinanceInvoiceFee.gibbonFinanceFeeID=gibbonFinanceFee.gibbonFinanceFeeID)
					WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID" ;
			}
			else {
				$sqlTotal="SELECT gibbonFinanceInvoiceFee.fee AS fee, NULL AS fee2
					FROM gibbonFinanceInvoiceFee
					WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID" ;
			}
			if (is_null($resultTotal=$pdo->executeQuery($dataTotal, $sqlTotal)))
			{
				$excel->getActiveSheet()->setCellValueByColumnAndRow(8, $r, 'Error calculating total');
				$feeError = true;
			}
			while ($rowTotal = $resultTotal->fetch()) {
				if (is_numeric($rowTotal["fee2"])) {
					$totalFee+=$rowTotal["fee2"] ;
				}
				else {
					$totalFee+=$rowTotal["fee"] ;
				}
			}
			$x = '';
			if (! $feeError) {
				if (substr($_SESSION[$guid]["currency"],4)!="") {
					$x .= substr($_SESSION[$guid]["currency"],4) . " " ;
				}
				$x .= number_format($totalFee, 2, ".", ",") ;
				$excel->getActiveSheet()->setCellValueByColumnAndRow(8, $r, $x);
			}
            $excel->getActiveSheet()->getStyleByColumnAndRow(8, $r)->applyFromArray($style_border);
			//Column J
		    $excel->getActiveSheet()->setCellValueByColumnAndRow(9, $r, dateConvertBack($guid, $row["invoiceIssueDate"]));
            $excel->getActiveSheet()->getStyleByColumnAndRow(9, $r)->applyFromArray($style_border);
			//Column K
			$excel->getActiveSheet()->setCellValueByColumnAndRow(10, $r, dateConvertBack($guid, $row["invoiceDueDate"]));
            $excel->getActiveSheet()->getStyleByColumnAndRow(10, $r)->applyFromArray($style_border);
			//Column L
            if ($row["paidDate"]!="")
				$excel->getActiveSheet()->setCellValueByColumnAndRow(11, $r, dateConvertBack($guid, $row["paidDate"]));
            else
                $excel->getActiveSheet()->setCellValueByColumnAndRow(11, $r, '');
            $excel->getActiveSheet()->getStyleByColumnAndRow(11, $r)->applyFromArray($style_border);
			//Column M
			$excel->getActiveSheet()->setCellValueByColumnAndRow(12, $r, number_format($row["paidAmount"], 2, ".", ","));
            $excel->getActiveSheet()->getStyleByColumnAndRow(12, $r)->applyFromArray($style_border);
			$r++;
		}

		$_SESSION[$guid]['financeInvoiceExportIDs'] = null;
		$excel->exportWorksheet();
	}
}
