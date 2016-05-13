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

//Get alternative header names
$attainmentAlternativeName = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeName');
$attainmentAlternativeNameAbrev = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeNameAbrev');
$effortAlternativeName = getSettingByScope($connection2, 'Markbook', 'effortAlternativeName');
$effortAlternativeNameAbrev = getSettingByScope($connection2, 'Markbook', 'effortAlternativeNameAbrev');

@session_start();

$gibbonCourseClassID = $_SESSION[$guid]['exportToExcelParams'];
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


		$excel->getActiveSheet()->setCellValueByColumnAndRow(0, 1, __($guid, 'Student'));

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
				$excel->getActiveSheet()->setCellValueByColumnAndRow(2, 1, $row['name']);
            }
        }


		if ($attainmentAlternativeNameAbrev != '') {
			$x = $attainmentAlternativeNameAbrev;
		} else {
			$x = __($guid, 'Att');
		}
		$excel->getActiveSheet()->setCellValueByColumnAndRow(1, 2, $x);
                if ($effortAlternativeNameAbrev != '') {
                    $x = $effortAlternativeNameAbrev;
                } else {
                    $x = __($guid, 'Eff');
                }
		$excel->getActiveSheet()->setCellValueByColumnAndRow(2, 2, $x);
		$excel->getActiveSheet()->setCellValueByColumnAndRow(3, 2, __($guid, 'Comment')." ".__($guid, 'Com'));

		$r = 2;

        $count = 0;
        $rowNum = 'odd';

		$dataStudents = array('gibbonCourseClassID' => $gibbonCourseClassID);
		$sqlStudents = "SELECT title, surname, preferredName, gibbonPerson.gibbonPersonID, dateStart FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Student' AND gibbonCourseClassID=:gibbonCourseClassID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') ORDER BY surname, preferredName";
		$resultStudents = $pdo->executeQuery($dataStudent, $sqlStudents);
        if ($resultStudents->rowCount() < 1) {
			$excel->getActiveSheet()->setCellValueByColumnAndRow(0, 3, __($guid, 'There are no records to display.'));
        } else {
            while ($rowStudents = $resultStudents->fetch()) {
                $r++;
                ++$count;
				//Column A
				$excel->getActiveSheet()->setCellValueByColumnAndRow(0, $r, formatName('', $rowStudents['preferredName'], $rowStudents['surname'], 'Student', true));
				//Column B

                for ($i = 0;$i < $columns;++$i) {
                    $row = $result->fetch();
					$dataEntry = array('gibbonMarkbookColumnID' => $columnID[($i)], 'gibbonPersonIDStudent' => $rowStudents['gibbonPersonID']);
					$sqlEntry = 'SELECT * FROM gibbonMarkbookEntry WHERE gibbonMarkbookColumnID=:gibbonMarkbookColumnID AND gibbonPersonIDStudent=:gibbonPersonIDStudent';
					$resultEntry = $pdo->executeQuery($dataEntry, $sqlEntry);

                    if ($resultEntry->rowCount() == 1) {
                        $rowEntry = $resultEntry->fetch();
                        $styleAttainment = '';
                        if ($rowEntry['attainmentConcern'] == 'Y') {
                            $styleAttainment = "style='color: #".$alert['color'].'; font-weight: bold; border: 2px solid #'.$alert['color'].'; padding: 2px 4px; background-color: #'.$alert['colorBG']."'";
                        } elseif ($rowEntry['attainmentConcern'] == 'P') {
                            $styleAttainment = "style='color: #390; font-weight: bold; border: 2px solid #390; padding: 2px 4px; background-color: #D4F6DC'";
                        }
 //                       echo "<td style='border-left: 2px solid #666; text-align: center'>";
                        $attainment = '';
                        if ($rowEntry['attainmentValue'] != '') {
                            $attainment = __($guid, $rowEntry['attainmentValue']);
                        }
                        if ($rowEntry['attainmentValue'] == 'Complete') {
                            $attainment = __($guid, 'Com');
                        } elseif ($rowEntry['attainmentValue'] == 'Incomplete') {
                            $attainment = __($guid, 'Inc');
                        }
						//Column B
						$excel->getActiveSheet()->setCellValueByColumnAndRow(1, $r, htmlPrep($rowEntry['attainmentDescriptor']).' '.$attainment);
                        $styleEffort = '';
                        if ($rowEntry['effortConcern'] == 'Y') {
                            $styleEffort = "style='color: #".$alert['color'].'; font-weight: bold; border: 2px solid #'.$alert['color'].'; padding: 2px 4px; background-color: #'.$alert['colorBG']."'";
                        }
                        $effort = '';
                        if ($rowEntry['effortValue'] != '') {
                            $effort = __($guid, $rowEntry['effortValue']);
                        }
                        if ($rowEntry['effortValue'] == 'Complete') {
                            $effort = __($guid, 'Com');
                        } elseif ($rowEntry['effortValue'] == 'Incomplete') {
                            $effort = __($guid, 'Inc');
                        }
 						//Column C
						$excel->getActiveSheet()->setCellValueByColumnAndRow(2, $r, $rowEntry['effortDescriptor']);
 						//Column D
                        $style = '';
                        if ($rowEntry['comment'] != '') {
							$excel->getActiveSheet()->setCellValueByColumnAndRow(3, $r, $rowEntry['comment']);
                        }
                    }
                }
            }
			$_SESSION[$guid]['exportToExcelParams'] = '';
			$excel->exportWorksheet();
        }
    }
}

