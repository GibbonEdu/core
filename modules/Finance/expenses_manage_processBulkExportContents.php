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



		$excel = new Gibbon\Excel('expenses.xlsx');
		if ($excel->estimateCellCount($pdo) > 8000)    //  If too big, then render csv instead.
			return Gibbon\csv::generate($pdo, 'Invoices');
		$excel->setActiveSheetIndex(0);
		$excel->getProperties()->setTitle('Expenses');
		$excel->getProperties()->setSubject('Expense Export');
		$excel->getProperties()->setDescription('Expense Export');

        //Create border and fill style
        $style_border = array('borders' => array('right' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => '766f6e')), 'left' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => '766f6e')), 'top' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => '766f6e')), 'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => '766f6e'))));
        $style_head_fill = array('fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => 'B89FE2')));

        //Auto set column widths
        for($col = 'A'; $col !== 'I'; $col++)
            $excel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);

		$excel->getActiveSheet()->setCellValueByColumnAndRow(0, 1, __($guid, 'Expense Number'));
        $excel->getActiveSheet()->getStyleByColumnAndRow(0, 1)->applyFromArray($style_border);
        $excel->getActiveSheet()->getStyleByColumnAndRow(0, 1)->applyFromArray($style_head_fill);
		$excel->getActiveSheet()->setCellValueByColumnAndRow(1, 1, __($guid, 'Budget'));
        $excel->getActiveSheet()->getStyleByColumnAndRow(1, 1)->applyFromArray($style_border);
        $excel->getActiveSheet()->getStyleByColumnAndRow(1, 1)->applyFromArray($style_head_fill);
		$excel->getActiveSheet()->setCellValueByColumnAndRow(2, 1, __($guid, 'Budget Cycle'));
        $excel->getActiveSheet()->getStyleByColumnAndRow(2, 1)->applyFromArray($style_border);
        $excel->getActiveSheet()->getStyleByColumnAndRow(2, 1)->applyFromArray($style_head_fill);
		$excel->getActiveSheet()->setCellValueByColumnAndRow(3, 1, __($guid, 'Title'));
        $excel->getActiveSheet()->getStyleByColumnAndRow(3, 1)->applyFromArray($style_border);
        $excel->getActiveSheet()->getStyleByColumnAndRow(3, 1)->applyFromArray($style_head_fill);
		$excel->getActiveSheet()->setCellValueByColumnAndRow(4, 1, __($guid, 'Status'));
        $excel->getActiveSheet()->getStyleByColumnAndRow(4, 1)->applyFromArray($style_border);
        $excel->getActiveSheet()->getStyleByColumnAndRow(4, 1)->applyFromArray($style_head_fill);
		$excel->getActiveSheet()->setCellValueByColumnAndRow(5, 1, __($guid, 'Cost')." (".$_SESSION[$guid]['currency'].')');
        $excel->getActiveSheet()->getStyleByColumnAndRow(5, 1)->applyFromArray($style_border);
        $excel->getActiveSheet()->getStyleByColumnAndRow(5, 1)->applyFromArray($style_head_fill);
		$excel->getActiveSheet()->setCellValueByColumnAndRow(6, 1, __($guid, 'Staff'));
        $excel->getActiveSheet()->getStyleByColumnAndRow(6, 1)->applyFromArray($style_border);
        $excel->getActiveSheet()->getStyleByColumnAndRow(6, 1)->applyFromArray($style_head_fill);
		$excel->getActiveSheet()->setCellValueByColumnAndRow(7, 1, __($guid, 'Timestamp'));
        $excel->getActiveSheet()->getStyleByColumnAndRow(7, 1)->applyFromArray($style_border);
        $excel->getActiveSheet()->getStyleByColumnAndRow(7, 1)->applyFromArray($style_head_fill);
		$excel->getActiveSheet()->getStyle("1:1")->getFont()->setBold(true);


        $count = 1;
        while ($row = $result->fetch()) {
            ++$count;
 			//Column A
			$excel->getActiveSheet()->setCellValueByColumnAndRow(0, $count, $row['gibbonFinanceExpenseID']);
            $excel->getActiveSheet()->getStyleByColumnAndRow(0, $count)->applyFromArray($style_border);
            //Column B
			$excel->getActiveSheet()->setCellValueByColumnAndRow(1, $count, $row['budget']);
            $excel->getActiveSheet()->getStyleByColumnAndRow(1, $count)->applyFromArray($style_border);
 			//Column C
			$excel->getActiveSheet()->setCellValueByColumnAndRow(2, $count, $row['budgetCycle']);
            $excel->getActiveSheet()->getStyleByColumnAndRow(2, $count)->applyFromArray($style_border);
 			//Column D
			$excel->getActiveSheet()->setCellValueByColumnAndRow(3, $count, $row['title']);
            $excel->getActiveSheet()->getStyleByColumnAndRow(3, $count)->applyFromArray($style_border);
 			//Column E
			$excel->getActiveSheet()->setCellValueByColumnAndRow(4, $count, $row['status']);
            $excel->getActiveSheet()->getStyleByColumnAndRow(4, $count)->applyFromArray($style_border);
 			//Column F
			$excel->getActiveSheet()->setCellValueByColumnAndRow(5, $count, number_format($row['cost'], 2, '.', ','));
            $excel->getActiveSheet()->getStyleByColumnAndRow(5, $count)->applyFromArray($style_border);
 			//Column G
			$excel->getActiveSheet()->setCellValueByColumnAndRow(6, $count, formatName('', $row['preferredName'], $row['surname'], 'Staff', true, true));
            $excel->getActiveSheet()->getStyleByColumnAndRow(6, $count)->applyFromArray($style_border);
 			//Column H
			$excel->getActiveSheet()->setCellValueByColumnAndRow(7, $count, $row['timestampCreator']);
            $excel->getActiveSheet()->getStyleByColumnAndRow(7, $count)->applyFromArray($style_border);
        }
        if ($count == 0) {
 			//Column A
			$excel->getActiveSheet()->setCellValueByColumnAndRow(0, $count, __($guid, 'There are no records to display.'));
        }
	    $_SESSION[$guid]['financeExpenseExportIDs'] = null;
		$excel->exportWorksheet();
    }
}
