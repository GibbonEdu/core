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

$gibbonCourseClassID = $_GET['gibbonCourseClassID'];
$gibbonMarkbookColumnID = $_SESSION[$guid]['exportToExcelParams'];
if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_view.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    $alert = getAlert($guid, $connection2, 002);

    //Proceed!

	$dataStudents = array('gibbonCourseClassID' => $gibbonCourseClassID);
	$sqlStudents = "SELECT title, surname, preferredName, gibbonPerson.gibbonPersonID, dateStart 
		FROM gibbonCourseClassPerson 
			JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) 
		WHERE role='Student' 
			AND gibbonCourseClassID=:gibbonCourseClassID 
			AND status='Full' 
			AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') 
			AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') 
		ORDER BY surname, preferredName";
	$resultStudents = $pdo->executeQuery($dataStudents, $sqlStudents, '_');
    if ($resultStudents->rowCount() < 1) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {

		$excel = new Gibbon\Excel('markbookColumn.xlsx');
		if ($excel->estimateCellCount($pdo) > 8000)    //  If too big, then render csv instead.
			return Gibbon\csv::generate($pdo, 'markbookColumn');
		$excel->setActiveSheetIndex(0);
		$excel->getProperties()->setTitle('Markbook Data');
		$excel->getProperties()->setSubject('Markbook Data');
		$excel->getProperties()->setDescription('Markbook Data');
		
		
		$excel->getActiveSheet()->setCellValueByColumnAndRow(0, 1, __($guid, 'Student'));
        if ($attainmentAlternativeName != '') {
            $x = $attainmentAlternativeName;
        } else {
            $x = __($guid, 'Attainment');
        }
		$excel->getActiveSheet()->setCellValueByColumnAndRow(1, 1, $x);
        if ($effortAlternativeName != '') {
            $x = $effortAlternativeName;
        } else {
            $x = __($guid, 'Effort');
        }
		$excel->getActiveSheet()->setCellValueByColumnAndRow(2, 1, $x);
		$excel->getActiveSheet()->setCellValueByColumnAndRow(3, 1, __($guid, 'Comment'));
		$excel->getActiveSheet()->getStyle("1:1")->getFont()->setBold(true);

		$r = 1;
        while ($rowStudents = $resultStudents->fetch()) {
            //COLOR ROW BY STATUS!
			$r++;
			//Column A
			$excel->getActiveSheet()->setCellValueByColumnAndRow(0, $r, formatName('', $rowStudents['preferredName'], $rowStudents['surname'], 'Student', true));
            echo formatName('', $rowStudents['preferredName'], $rowStudents['surname'], 'Student', true);

			//Column B
			$x = '';
			$dataEntry = array('gibbonMarkbookColumnID' => $gibbonMarkbookColumnID, 'gibbonPersonIDStudent' => $rowStudents['gibbonPersonID']);
			$sqlEntry = 'SELECT * 
				FROM gibbonMarkbookEntry 
				WHERE gibbonMarkbookColumnID=:gibbonMarkbookColumnID 
					AND gibbonPersonIDStudent=:gibbonPersonIDStudent';
			if (is_null($resultEntry = $pdo->executeQuery($dataEntry, $sqlEntry))) {
				$x .= $pdo->getError();
			}
            if ($resultEntry->rowCount() == 1) {
                $rowEntry = $resultEntry->fetch();
                $styleAttainment = '';
                if ($rowEntry['attainmentConcern'] == 'Y') {
                    $styleAttainment = "style='color: #".$alert['color'].'; font-weight: bold; border: 2px solid #'.$alert['color'].'; padding: 2px 4px; background-color: #'.$alert['colorBG']."'";
                }
                $attainment = $rowEntry['attainmentValue'];
                if ($rowEntry['attainmentValue'] == 'Complete') {
                    $attainment = 'CO';
                } elseif ($rowEntry['attainmentValue'] == 'Incomplete') {
                    $attainment = 'IC';
                }
                $x .= htmlPrep($rowEntry['attainmentDescriptor'].' '.$attainment;
				$excel->getActiveSheet()->setCellValueByColumnAndRow(1, $r, $x);
                $styleEffort = '';
                if ($rowEntry['effortConcern'] == 'Y') {
                    $styleEffort = "style='color: #".$alert['color'].'; font-weight: bold; border: 2px solid #'.$alert['color'].'; padding: 2px 4px; background-color: #'.$alert['colorBG']."'";
                }
                $effort = $rowEntry['effortValue'];
                if ($rowEntry['effortValue'] == 'Complete') {
                    $effort = 'CO';
                } elseif ($rowEntry['effortValue'] == 'Incomplete') {
                    $effort = 'IC';
                }
				$excel->getActiveSheet()->setCellValueByColumnAndRow(2, $r, htmlPrep($rowEntry['effortDescriptor']).' '.$effort);
                $style = '';
                if ($rowEntry['comment'] != '') {
                	$excel->getActiveSheet()->setCellValueByColumnAndRow(2, $r, htmlPrep($rowEntry['comment'])." ".substr($rowEntry['comment'], 0, 10));
                }
            } else {
				$excel->getActiveSheet()->setCellValueByColumnAndRow(1, $r, 'No data.');
            }
        }
    }
	$_SESSION[$guid]['exportToExcelParams'] = '';
	$excel->exportWorksheet();
}

