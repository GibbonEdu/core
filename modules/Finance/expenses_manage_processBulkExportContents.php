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

use Gibbon\Services\Format;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

include '../../config.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/expenses_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $financeExpenseExportIDs = $session->get('financeExpenseExportIDs');
    $gibbonFinanceBudgetCycleID = $_GET['gibbonFinanceBudgetCycleID'] ?? '';

    if ($financeExpenseExportIDs == '' or $gibbonFinanceBudgetCycleID == '') {
        echo "<div class='error'>";
        echo __('List of invoices or budget cycle have not been specified, and so this export cannot be completed.');
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
        }



		$excel = new Gibbon\Excel('expenses.xlsx');
		if ($excel->estimateCellCount($result) > 8000)    //  If too big, then render csv instead.
			return Gibbon\csv::generate($pdo, 'Invoices');
		$excel->setActiveSheetIndex(0);
		$excel->getProperties()->setTitle('Expenses');
		$excel->getProperties()->setSubject('Expense Export');
		$excel->getProperties()->setDescription('Expense Export');

        //Create border and fill style
        $style_border = array('borders' => array('right' => array('borderStyle' => Border::BORDER_THIN, 'color' => array('argb' => '766f6e')), 'left' => array('borderStyle' => Border::BORDER_THIN, 'color' => array('argb' => '766f6e')), 'top' => array('borderStyle' => Border::BORDER_THIN, 'color' => array('argb' => '766f6e')), 'bottom' => array('borderStyle' => Border::BORDER_THIN, 'color' => array('argb' => '766f6e'))));
        $style_head_fill = array('fill' => array('fillType' => Fill::FILL_SOLID, 'color' => array('rgb' => 'B89FE2')));

        //Auto set column widths
        for($col = 'A'; $col !== 'I'; $col++)
            $excel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);

		$excel->getActiveSheet()->setCellValueByColumnAndRow(1, 1, __('Expense Number'));
        $excel->getActiveSheet()->getStyleByColumnAndRow(1, 1)->applyFromArray($style_border);
        $excel->getActiveSheet()->getStyleByColumnAndRow(1, 1)->applyFromArray($style_head_fill);
		$excel->getActiveSheet()->setCellValueByColumnAndRow(2, 1, __('Budget'));
        $excel->getActiveSheet()->getStyleByColumnAndRow(2, 1)->applyFromArray($style_border);
        $excel->getActiveSheet()->getStyleByColumnAndRow(2, 1)->applyFromArray($style_head_fill);
		$excel->getActiveSheet()->setCellValueByColumnAndRow(3, 1, __('Budget Cycle'));
        $excel->getActiveSheet()->getStyleByColumnAndRow(3, 1)->applyFromArray($style_border);
        $excel->getActiveSheet()->getStyleByColumnAndRow(3, 1)->applyFromArray($style_head_fill);
		$excel->getActiveSheet()->setCellValueByColumnAndRow(4, 1, __('Title'));
        $excel->getActiveSheet()->getStyleByColumnAndRow(4, 1)->applyFromArray($style_border);
        $excel->getActiveSheet()->getStyleByColumnAndRow(4, 1)->applyFromArray($style_head_fill);
		$excel->getActiveSheet()->setCellValueByColumnAndRow(5, 1, __('Status'));
        $excel->getActiveSheet()->getStyleByColumnAndRow(5, 1)->applyFromArray($style_border);
        $excel->getActiveSheet()->getStyleByColumnAndRow(5, 1)->applyFromArray($style_head_fill);
		$excel->getActiveSheet()->setCellValueByColumnAndRow(6, 1, __('Cost')." (".$session->get('currency').')');
        $excel->getActiveSheet()->getStyleByColumnAndRow(6, 1)->applyFromArray($style_border);
        $excel->getActiveSheet()->getStyleByColumnAndRow(6, 1)->applyFromArray($style_head_fill);
		$excel->getActiveSheet()->setCellValueByColumnAndRow(7, 1, __('Staff'));
        $excel->getActiveSheet()->getStyleByColumnAndRow(7, 1)->applyFromArray($style_border);
        $excel->getActiveSheet()->getStyleByColumnAndRow(7, 1)->applyFromArray($style_head_fill);
		$excel->getActiveSheet()->setCellValueByColumnAndRow(8, 1, __('Timestamp'));
        $excel->getActiveSheet()->getStyleByColumnAndRow(8, 1)->applyFromArray($style_border);
        $excel->getActiveSheet()->getStyleByColumnAndRow(8, 1)->applyFromArray($style_head_fill);
		$excel->getActiveSheet()->getStyle("1:1")->getFont()->setBold(true);


        $count = 1;
        while ($row = $result->fetch()) {
            ++$count;
 			//Column A
			$excel->getActiveSheet()->setCellValueByColumnAndRow(1, $count, $row['gibbonFinanceExpenseID']);
            $excel->getActiveSheet()->getStyleByColumnAndRow(1, $count)->applyFromArray($style_border);
            //Column B
			$excel->getActiveSheet()->setCellValueByColumnAndRow(2, $count, $row['budget']);
            $excel->getActiveSheet()->getStyleByColumnAndRow(2, $count)->applyFromArray($style_border);
 			//Column C
			$excel->getActiveSheet()->setCellValueByColumnAndRow(3, $count, $row['budgetCycle']);
            $excel->getActiveSheet()->getStyleByColumnAndRow(3, $count)->applyFromArray($style_border);
 			//Column D
			$excel->getActiveSheet()->setCellValueByColumnAndRow(4, $count, $row['title']);
            $excel->getActiveSheet()->getStyleByColumnAndRow(4, $count)->applyFromArray($style_border);
 			//Column E
			$excel->getActiveSheet()->setCellValueByColumnAndRow(5, $count, $row['status']);
            $excel->getActiveSheet()->getStyleByColumnAndRow(5, $count)->applyFromArray($style_border);
 			//Column F
			$excel->getActiveSheet()->setCellValueByColumnAndRow(6, $count, number_format($row['cost'], 2, '.', ','));
            $excel->getActiveSheet()->getStyleByColumnAndRow(6, $count)->applyFromArray($style_border);
 			//Column G
			$excel->getActiveSheet()->setCellValueByColumnAndRow(7, $count, Format::name('', $row['preferredName'], $row['surname'], 'Staff', true, true));
            $excel->getActiveSheet()->getStyleByColumnAndRow(7, $count)->applyFromArray($style_border);
 			//Column H
			$excel->getActiveSheet()->setCellValueByColumnAndRow(8, $count, $row['timestampCreator']);
            $excel->getActiveSheet()->getStyleByColumnAndRow(8, $count)->applyFromArray($style_border);
        }
        if ($count == 0) {
 			//Column A
			$excel->getActiveSheet()->setCellValueByColumnAndRow(0, $count, __('There are no records to display.'));
        }
	    $session->set('financeExpenseExportIDs', null);
		$excel->exportWorksheet();
    }
}
