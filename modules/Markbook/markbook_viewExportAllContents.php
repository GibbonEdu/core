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

//Get settings
$enableEffort = getSettingByScope($connection2, 'Markbook', 'enableEffort');
$enableRubrics = getSettingByScope($connection2, 'Markbook', 'enableRubrics');
$attainmentAlternativeName = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeName');
$attainmentAlternativeNameAbrev = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeNameAbrev');
$effortAlternativeName = getSettingByScope($connection2, 'Markbook', 'effortAlternativeName');
$effortAlternativeNameAbrev = getSettingByScope($connection2, 'Markbook', 'effortAlternativeNameAbrev');

//Set up adjustment for presence of effort column or not
if ($enableEffort == 'Y')
    $effortAdjust = 0 ;
else
    $effortAdjust = 1 ;

@session_start();

if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_view.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    $alert = getAlert($guid, $connection2, 002);

    //Count number of columns
	$data = array('gibbonCourseClassID' => $gibbonCourseClassID);
	$sql = 'SELECT * FROM gibbonMarkbookColumn WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY complete, completeDate DESC';
	$result = $pdo->executeQuery($data, $sql, '_');
    $columns = $result->rowCount();
    if ($columns < 1) {
        echo "<div class='warning'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        //Print table header
		$excel = new Gibbon\Excel('markbookAll.xlsx');
		if ($excel->estimateCellCount($pdo) > 8000)    //  If too big, then render csv instead.
			return Gibbon\csv::generate($pdo, 'markbookColumn');
		$excel->setActiveSheetIndex(0);
		$excel->getProperties()->setTitle('All Markbook Data');
		$excel->getProperties()->setSubject('All Markbook Data');
		$excel->getProperties()->setDescription('All Markbook Data');

        //Create border and fill style
        $style_border = array('borders' => array('right' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => '766f6e')), 'left' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => '766f6e')), 'top' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => '766f6e')), 'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => '766f6e'))));
        $style_head_fill = array('fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => 'B89FE2')));
        $style_head_fill2 = array('fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => 'C5D9F1')));

        //Auto set first column width
        $excel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);

		$excel->getActiveSheet()->setCellValueByColumnAndRow(0, 1, __($guid, 'Student'));
        $excel->getActiveSheet()->getStyleByColumnAndRow(0, 1)->applyFromArray($style_border);
        $excel->getActiveSheet()->getStyleByColumnAndRow(0, 1)->applyFromArray($style_head_fill);

        $span = 3;
        $columnID = array();
        $attainmentID = array();
        $effortID = array();
        for ($i = 0;$i < $columns;++$i) {
            $row = $result->fetch();
            if ($row === false) {
                $columnID[$i] = false;
            } else {
                $columnID[$i] = $row['gibbonMarkbookColumnID'];
                $attainmentID[$i] = $row['gibbonScaleIDAttainment'];
                $effortID[$i] = $row['gibbonScaleIDEffort'];
                $gibbonPlannerEntryID[$i] = $row['gibbonPlannerEntryID'];
                $gibbonRubricIDAttainment[$i] = $row['gibbonRubricIDAttainment'];
                $gibbonRubricIDEffort[$i] = $row['gibbonRubricIDEffort'];
            }

            if ($columnID[$i]) {
				$excel->getActiveSheet()->setCellValueByColumnAndRow((1 + ($i * (3-$effortAdjust))), 1, $row['name']);
                $excel->getActiveSheet()->getStyleByColumnAndRow((1 + ($i * (3-$effortAdjust))), 1)->applyFromArray($style_border);
                $excel->getActiveSheet()->getStyleByColumnAndRow((1 + ($i * (3-$effortAdjust))), 1)->applyFromArray($style_head_fill);
                $excel->getActiveSheet()->getStyleByColumnAndRow((2 + ($i * (3-$effortAdjust))), 1)->applyFromArray($style_border);
                $excel->getActiveSheet()->getStyleByColumnAndRow((2 + ($i * (3-$effortAdjust))), 1)->applyFromArray($style_head_fill);
                $excel->getActiveSheet()->getStyleByColumnAndRow(((3-$effortAdjust) + ($i * (3-$effortAdjust))), 1)->applyFromArray($style_border);
                $excel->getActiveSheet()->getStyleByColumnAndRow(((3-$effortAdjust) + ($i * (3-$effortAdjust))), 1)->applyFromArray($style_head_fill);
                }

            $excel->getActiveSheet()->getStyleByColumnAndRow(0, 2)->applyFromArray($style_border);
            $excel->getActiveSheet()->getStyleByColumnAndRow(0, 2)->applyFromArray($style_head_fill2);

            if ($attainmentAlternativeNameAbrev != '') {
    			$x = $attainmentAlternativeNameAbrev;
    		} else {
    			$x = __($guid, 'Att');
    		}
    		$excel->getActiveSheet()->setCellValueByColumnAndRow((1 + ($i * (3-$effortAdjust))), 2, $x);
            $excel->getActiveSheet()->getStyleByColumnAndRow((1 + ($i * (3-$effortAdjust))), 2)->applyFromArray($style_border);
            $excel->getActiveSheet()->getStyleByColumnAndRow((1 + ($i * (3-$effortAdjust))), 2)->applyFromArray($style_head_fill2);
            if ($enableEffort == 'Y') {
                if ($effortAlternativeNameAbrev != '') {
                    $x = $effortAlternativeNameAbrev;
                } else {
                    $x = __($guid, 'Eff');
                }
        		$excel->getActiveSheet()->setCellValueByColumnAndRow((2 + ($i * (3-$effortAdjust))), 2, $x);
                $excel->getActiveSheet()->getStyleByColumnAndRow((2 + ($i * (3-$effortAdjust))), 2)->applyFromArray($style_border);
                $excel->getActiveSheet()->getStyleByColumnAndRow((2 + ($i * (3-$effortAdjust))), 2)->applyFromArray($style_head_fill2);
            }
            $excel->getActiveSheet()->setCellValueByColumnAndRow(((3-$effortAdjust) + ($i * (3-$effortAdjust))), 2, __($guid, 'Com'));
            $excel->getActiveSheet()->getStyleByColumnAndRow(((3-$effortAdjust) + ($i * (3-$effortAdjust))), 2)->applyFromArray($style_border);
            $excel->getActiveSheet()->getStyleByColumnAndRow(((3-$effortAdjust) + ($i * (3-$effortAdjust))), 2)->applyFromArray($style_head_fill2);
        }

		$r = 2;

        $count = 0;
        $rowNum = 'odd';

		$dataStudents = array('gibbonCourseClassID' => $gibbonCourseClassID);
		$sqlStudents = "SELECT title, surname, preferredName, gibbonPerson.gibbonPersonID, dateStart FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Student' AND gibbonCourseClassID=:gibbonCourseClassID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') ORDER BY surname, preferredName";
		$resultStudents = $pdo->executeQuery($dataStudents, $sqlStudents);
        if ($resultStudents->rowCount() < 1) {
			$excel->getActiveSheet()->setCellValueByColumnAndRow(0, 3, __($guid, 'There are no records to display.'));
            $excel->getActiveSheet()->getStyleByColumnAndRow(0, 3)->applyFromArray($style_border);

        } else {
            while ($rowStudents = $resultStudents->fetch()) {
                $r++;
                ++$count;
				//Column A
				$excel->getActiveSheet()->setCellValueByColumnAndRow(0, $r, formatName('', $rowStudents['preferredName'], $rowStudents['surname'], 'Student', true));
                $excel->getActiveSheet()->getStyleByColumnAndRow(0, $r)->applyFromArray($style_border);

				//Columns following A
                for ($i = 0;$i < $columns;++$i) {
                    $row = $result->fetch();
					$dataEntry = array('gibbonMarkbookColumnID' => $columnID[($i)], 'gibbonPersonIDStudent' => $rowStudents['gibbonPersonID']);
					$sqlEntry = 'SELECT * FROM gibbonMarkbookEntry WHERE gibbonMarkbookColumnID=:gibbonMarkbookColumnID AND gibbonPersonIDStudent=:gibbonPersonIDStudent';
					$resultEntry = $pdo->executeQuery($dataEntry, $sqlEntry);

                    if ($resultEntry->rowCount() == 1) {
                        $rowEntry = $resultEntry->fetch();
                        $attainment = '';
                        if ($rowEntry['attainmentValue'] != '') {
                            $attainment = __($guid, $rowEntry['attainmentValue']);
                        }
                        if ($rowEntry['attainmentValue'] == 'Complete') {
                            $attainment = __($guid, 'Com');
                        } elseif ($rowEntry['attainmentValue'] == 'Incomplete') {
                            $attainment = __($guid, 'Inc');
                        }
						$excel->getActiveSheet()->setCellValueByColumnAndRow((1 + ($i * (3-$effortAdjust))), $r, htmlPrep($rowEntry['attainmentValue']));
                        $excel->getActiveSheet()->getStyleByColumnAndRow((1 + ($i * (3-$effortAdjust))), $r)->applyFromArray($style_border);

                        $effort = '';
                        if ($rowEntry['effortValue'] != '') {
                            $effort = __($guid, $rowEntry['effortValue']);
                        }
                        if ($rowEntry['effortValue'] == 'Complete') {
                            $effort = __($guid, 'Com');
                        } elseif ($rowEntry['effortValue'] == 'Incomplete') {
                            $effort = __($guid, 'Inc');
                        }
 						if ($enableEffort == 'Y') {
                            $excel->getActiveSheet()->setCellValueByColumnAndRow((2 + ($i * (3-$effortAdjust))), $r, $rowEntry['effortValue']);
                            $excel->getActiveSheet()->getStyleByColumnAndRow((2 + ($i * (3-$effortAdjust))), $r)->applyFromArray($style_border);
                        }
                        $excel->getActiveSheet()->setCellValueByColumnAndRow(((3-$effortAdjust) + ($i * (3-$effortAdjust))), $r, $rowEntry['comment']);
                        $excel->getActiveSheet()->getStyleByColumnAndRow(((3-$effortAdjust) + ($i * (3-$effortAdjust))), $r)->applyFromArray($style_border);
                    } else { //Fill empty spaces
                        $excel->getActiveSheet()->setCellValueByColumnAndRow((1 + ($i * (3-$effortAdjust))), $r, '');
                        $excel->getActiveSheet()->getStyleByColumnAndRow((1 + ($i * (3-$effortAdjust))), $r)->applyFromArray($style_border);
                        $excel->getActiveSheet()->setCellValueByColumnAndRow((2 + ($i * (3-$effortAdjust))), $r, '');
                        $excel->getActiveSheet()->getStyleByColumnAndRow((2 + ($i * (3-$effortAdjust))), $r)->applyFromArray($style_border);
                        $excel->getActiveSheet()->setCellValueByColumnAndRow(((3-$effortAdjust) + ($i * (3-$effortAdjust))), $r, '');
                        $excel->getActiveSheet()->getStyleByColumnAndRow(((3-$effortAdjust) + ($i * (3-$effortAdjust))), $r)->applyFromArray($style_border);
                    }
                }
            }
			$excel->exportWorksheet();
        }
    }
}
