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

use Gibbon\Domain\System\AlertLevelGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Services\Format;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

include '../../config.php';

//Module includes
include './moduleFunctions.php';

//Get settings
$settingGateway = $container->get(SettingGateway::class);
$enableEffort = $settingGateway->getSettingByScope('Markbook', 'enableEffort');
$enableRubrics = $settingGateway->getSettingByScope('Markbook', 'enableRubrics');
$attainmentAlternativeName = $settingGateway->getSettingByScope('Markbook', 'attainmentAlternativeName');
$attainmentAlternativeNameAbrev = $settingGateway->getSettingByScope('Markbook', 'attainmentAlternativeNameAbrev');
$effortAlternativeName = $settingGateway->getSettingByScope('Markbook', 'effortAlternativeName');
$effortAlternativeNameAbrev = $settingGateway->getSettingByScope('Markbook', 'effortAlternativeNameAbrev');

//Set up adjustment for presence of effort column or not
if ($enableEffort == 'Y')
    $effortAdjust = 0 ;
else
    $effortAdjust = 1 ;

if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_view.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Check existence of and access to this class.
    $highestAction = getHighestGroupedAction($guid, '/modules/Markbook/markbook_view.php', $connection2);
    $class = getClass($pdo, $session->get('gibbonPersonID'), $gibbonCourseClassID, $highestAction);

    if (empty($class)) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    /**
     * @var AlertLevelGateway
     */
    $alertLevelGateway = $container->get(AlertLevelGateway::class);
    $alert = $alertLevelGateway->getByID(AlertLevelGateway::LEVEL_MEDIUM);

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
        echo $page->getBlankSlate();
    } else {

		$excel = new Gibbon\Excel('markbookColumn.xlsx');
		if ($excel->estimateCellCount($resultStudents) > 8000)    //  If too big, then render csv instead.
			return Gibbon\csv::generate($pdo, 'markbookColumn');
		$excel->setActiveSheetIndex(0);
		$excel->getProperties()->setTitle('Markbook Data');
		$excel->getProperties()->setSubject('Markbook Data');
		$excel->getProperties()->setDescription('Markbook Data');

        $excel->setActiveSheetIndex(0);

        //Create border and fill style
        $headerStyle = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => 'eeeeee'],
            ],
            'borders' => [
                'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => '444444'], ],
            ],
            'font' => [
                'size' => 12,
                'bold' => true,
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        $rowStyle = [
            'font' => [
                'size' => 12,
            ],
        ];

        //Auto set column widths
        for($col = 'A'; $col !== 'E'; $col++)
            $excel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);

		$excel->getActiveSheet()->setCellValueByColumnAndRow(1, 1, __('Student'));
        $excel->getActiveSheet()->getStyleByColumnAndRow(1, 1)->applyFromArray($headerStyle);

        if ($attainmentAlternativeNameAbrev != '') {
            $x = $attainmentAlternativeNameAbrev;
        } else {
            $x = __('Att');
        }
		$excel->getActiveSheet()->setCellValueByColumnAndRow(2, 1, $x);
        $excel->getActiveSheet()->getStyleByColumnAndRow(2, 1)->applyFromArray($headerStyle);
        if ($enableEffort == 'Y') {
            if ($effortAlternativeNameAbrev != '') {
                $x = $effortAlternativeNameAbrev;
            } else {
                $x = __('Eff');
            }
    		$excel->getActiveSheet()->setCellValueByColumnAndRow(3, 1, $x);
            $excel->getActiveSheet()->getStyleByColumnAndRow(3, 1)->applyFromArray($headerStyle);
        }
		$excel->getActiveSheet()->setCellValueByColumnAndRow((4-$effortAdjust), 1, __('Com'));
        $excel->getActiveSheet()->getStyleByColumnAndRow((4-$effortAdjust), 1)->applyFromArray($headerStyle);

		$r = 1;
        while ($rowStudents = $resultStudents->fetch()) {
            //COLOR ROW BY STATUS!
			$r++;
			//Column A
			$excel->getActiveSheet()->setCellValueByColumnAndRow(1, $r, Format::name('', $rowStudents['preferredName'], $rowStudents['surname'], 'Student', true));
            $excel->getActiveSheet()->getStyleByColumnAndRow(1, $r)->applyFromArray($rowStyle);

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
                $attainment = $rowEntry['attainmentValue'];
                if ($rowEntry['attainmentValue'] == 'Complete') {
                    $attainment = 'CO';
                } elseif ($rowEntry['attainmentValue'] == 'Incomplete') {
                    $attainment = 'IC';
                }
                $x .= htmlPrep($rowEntry['attainmentValue']);
				$excel->getActiveSheet()->setCellValueByColumnAndRow(2, $r, $x);
                $excel->getActiveSheet()->getStyleByColumnAndRow(2, $r)->applyFromArray($rowStyle);
                $effort = $rowEntry['effortValue'];
                if ($rowEntry['effortValue'] == 'Complete') {
                    $effort = 'CO';
                } elseif ($rowEntry['effortValue'] == 'Incomplete') {
                    $effort = 'IC';
                }
				if ($enableEffort == 'Y') {
                    $excel->getActiveSheet()->setCellValueByColumnAndRow(3, $r, htmlPrep($rowEntry['effortValue']));
                    $excel->getActiveSheet()->getStyleByColumnAndRow(3, $r)->applyFromArray($rowStyle);
                }
                $excel->getActiveSheet()->setCellValueByColumnAndRow((4-$effortAdjust), $r, htmlPrep($rowEntry['comment']));
                $excel->getActiveSheet()->getStyleByColumnAndRow((4-$effortAdjust), $r)->applyFromArray($rowStyle);
            } else {
				$excel->getActiveSheet()->setCellValueByColumnAndRow(2, $r, 'No data.');
                $excel->getActiveSheet()->getStyleByColumnAndRow(2, $r)->applyFromArray($rowStyle);
            }
        }
    }
	$excel->exportWorksheet();
}
