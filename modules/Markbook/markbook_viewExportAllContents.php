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
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use Gibbon\Module\Markbook\MarkbookView;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\AdvancedValueBinder;

include __DIR__ . '/../../config.php';

//Module includes
include __DIR__ . '/moduleFunctions.php';

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

    //Count number of columns
	$data = array('gibbonCourseClassID' => $gibbonCourseClassID);
	$sql = 'SELECT * FROM gibbonMarkbookColumn WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY complete, completeDate DESC';
	$result = $pdo->executeQuery($data, $sql, '_');
    $columns = $result->rowCount();
    if ($columns < 1) {
        echo "<div class='warning'>";
        echo __('There are no records to display.');
        echo '</div>';
    } else {

        require_once __DIR__ . '/src/MarkbookView.php';

        // Build the markbook object for this class
        $markbook = new MarkbookView($gibbon, $pdo, $gibbonCourseClassID, $settingGateway);

        // Calculate and cache all weighting data
        if ($markbook->getSetting('enableColumnWeighting') == 'Y') {
            $markbook->cacheWeightings( );
        }

        //Print table header
		$excel = new Gibbon\Excel('markbookAll.xlsx');
		if ($excel->estimateCellCount($result) > 8000)    //  If too big, then render csv instead.
			return Gibbon\csv::generate($pdo, 'markbookColumn');
		$excel->setActiveSheetIndex(0);
		$excel->getProperties()->setTitle('All Markbook Data');
		$excel->getProperties()->setSubject('All Markbook Data');
		$excel->getProperties()->setDescription('All Markbook Data');

        // Use advanced binder - better handling of numbers, percents, etc.
        Cell::setValueBinder( new AdvancedValueBinder() );

        $excel->setActiveSheetIndex(0);

        //Create border and fill style
        $style_border = array('borders' => array('right' => array('borderStyle' => Border::BORDER_THIN, 'color' => array('argb' => '766f6e')), 'left' => array('borderStyle' => Border::BORDER_THIN, 'color' => array('argb' => '766f6e')), 'top' => array('borderStyle' => Border::BORDER_THIN, 'color' => array('argb' => '766f6e')), 'bottom' => array('borderStyle' => Border::BORDER_THIN, 'color' => array('argb' => '766f6e'))));
        $style_head_fill = array('fill' => array('fillType' => Fill::FILL_SOLID, 'color' => array('rgb' => 'B89FE2')));
        $style_head_fill2 = array('fill' => array('fillType' => Fill::FILL_SOLID, 'color' => array('rgb' => 'C5D9F1')));

        //Auto set first column width
        $excel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);

		$excel->getActiveSheet()->setCellValueByColumnAndRow(1, 1, __('Student'));
        $excel->getActiveSheet()->getStyleByColumnAndRow(1, 1)->applyFromArray($style_border);
        $excel->getActiveSheet()->getStyleByColumnAndRow(1, 1)->applyFromArray($style_head_fill);

        $span = 3;
        $columnID = array();
        $attainmentID = array();
        $effortID = array();
        $subColCount = 3-$effortAdjust;

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
				$excel->getActiveSheet()->setCellValueByColumnAndRow((2 + ($i * ($subColCount))), 1, $row['name']);
                $excel->getActiveSheet()->getStyleByColumnAndRow((2 + ($i * ($subColCount))), 1)->applyFromArray($style_border);
                $excel->getActiveSheet()->getStyleByColumnAndRow((2 + ($i * ($subColCount))), 1)->applyFromArray($style_head_fill);
                $excel->getActiveSheet()->getStyleByColumnAndRow((3 + ($i * ($subColCount))), 1)->applyFromArray($style_border);
                $excel->getActiveSheet()->getStyleByColumnAndRow((3 + ($i * ($subColCount))), 1)->applyFromArray($style_head_fill);
                $excel->getActiveSheet()->getStyleByColumnAndRow((4 + ($i * ($subColCount))), 1)->applyFromArray($style_border);
                $excel->getActiveSheet()->getStyleByColumnAndRow((4 + ($i * ($subColCount))), 1)->applyFromArray($style_head_fill);
                }

            $excel->getActiveSheet()->getStyleByColumnAndRow(1, 2)->applyFromArray($style_border);
            $excel->getActiveSheet()->getStyleByColumnAndRow(1, 2)->applyFromArray($style_head_fill2);

            if ($attainmentAlternativeNameAbrev != '') {
    			$x = $attainmentAlternativeNameAbrev;
    		} else {
    			$x = __('Att');
    		}
    		$excel->getActiveSheet()->setCellValueByColumnAndRow((2 + ($i * ($subColCount))), 2, $x);
            $excel->getActiveSheet()->getStyleByColumnAndRow((2 + ($i * ($subColCount))), 2)->applyFromArray($style_border);
            $excel->getActiveSheet()->getStyleByColumnAndRow((2 + ($i * ($subColCount))), 2)->applyFromArray($style_head_fill2);
            if ($enableEffort == 'Y') {
                if ($effortAlternativeNameAbrev != '') {
                    $x = $effortAlternativeNameAbrev;
                } else {
                    $x = __('Eff');
                }
        		$excel->getActiveSheet()->setCellValueByColumnAndRow((3 + ($i * ($subColCount))), 2, $x);
                $excel->getActiveSheet()->getStyleByColumnAndRow((3 + ($i * ($subColCount))), 2)->applyFromArray($style_border);
                $excel->getActiveSheet()->getStyleByColumnAndRow((3 + ($i * ($subColCount))), 2)->applyFromArray($style_head_fill2);
            }
            $excel->getActiveSheet()->setCellValueByColumnAndRow((4 + ($i * ($subColCount))), 2, __('Com'));
            $excel->getActiveSheet()->getStyleByColumnAndRow((4 + ($i * ($subColCount))), 2)->applyFromArray($style_border);
            $excel->getActiveSheet()->getStyleByColumnAndRow((4 + ($i * ($subColCount))), 2)->applyFromArray($style_head_fill2);
        }

        $DAS = $markbook->getDefaultAssessmentScale();
        $terms = $markbook->getCurrentTerms();
        $markFormat = NumberFormat::FORMAT_GENERAL;

        if (isset($DAS['percent']) && $DAS['percent'] == '%') {
            $markFormat = NumberFormat::FORMAT_PERCENTAGE;
        }
        else if (isset($DAS['numeric']) && $DAS['numeric'] == 'Y') {
            $markFormat = NumberFormat::FORMAT_NUMBER;
        }

        // Add columns for Overall Grades, if enabled
        if ($markbook->getSetting('enableColumnWeighting') == 'Y') {
            $markSuffix = (isset($DAS['percent']))? $DAS['percent'] : '';

            $finalColumnNum = ($columns * ($subColCount));
            $finalColumnStart = $finalColumnNum+1;

            // Add Term Grades, if enabled & available
            if ($markbook->getSetting('enableGroupByTerm') == 'Y' && !empty($terms)) {

                foreach ($terms as $termCount => $term) {
                    $finalColumnNum++;
                    $excel->getActiveSheet()->getColumnDimension( $excel->num2alpha($finalColumnNum) )->setAutoSize(true);
                    $excel->getActiveSheet()->setCellValueByColumnAndRow( $finalColumnNum, 2, $term['name'] ?? $termCount );
                    $excel->getActiveSheet()->getStyleByColumnAndRow($finalColumnNum, 2)->applyFromArray($style_border);
                    $excel->getActiveSheet()->getStyleByColumnAndRow($finalColumnNum, 2)->applyFromArray($style_head_fill2);
                }
            }

            // Cumulative Average
            $finalColumnNum++;
            $excel->getActiveSheet()->getColumnDimension( $excel->num2alpha($finalColumnNum) )->setAutoSize(true);
            $excel->getActiveSheet()->setCellValueByColumnAndRow( $finalColumnNum, 2, __('Cumulative'));
            $excel->getActiveSheet()->getStyleByColumnAndRow($finalColumnNum, 2)->applyFromArray($style_border);
            $excel->getActiveSheet()->getStyleByColumnAndRow($finalColumnNum, 2)->applyFromArray($style_head_fill2);

            // Add Final Grades, if enabled & available
            if ($markbook->getSetting('enableTypeWeighting') == 'Y' && count($markbook->getGroupedMarkbookTypes('year')) > 0) {

                foreach ($markbook->getGroupedMarkbookTypes('year') as $type) {
                    // Final Weighted Types
                    $finalColumnNum++;
                    $excel->getActiveSheet()->getColumnDimension( $excel->num2alpha($finalColumnNum) )->setAutoSize(true);
                    $excel->getActiveSheet()->setCellValueByColumnAndRow( $finalColumnNum, 2, $type );
                    $excel->getActiveSheet()->getStyleByColumnAndRow($finalColumnNum, 2)->applyFromArray($style_border);
                    $excel->getActiveSheet()->getStyleByColumnAndRow($finalColumnNum, 2)->applyFromArray($style_head_fill2);
                }

                // Final Grade
                $finalColumnNum++;
                $excel->getActiveSheet()->getColumnDimension( $excel->num2alpha($finalColumnNum) )->setAutoSize(true);
                $excel->getActiveSheet()->setCellValueByColumnAndRow( $finalColumnNum, 2, __('Final Grade'));
                $excel->getActiveSheet()->getStyleByColumnAndRow($finalColumnNum, 2)->applyFromArray($style_border);
                $excel->getActiveSheet()->getStyleByColumnAndRow($finalColumnNum, 2)->applyFromArray($style_head_fill2);
            }

            $excel->getActiveSheet()->setCellValueByColumnAndRow( $finalColumnStart, 1, __('Overall Grades'));
            $excel->getActiveSheet()->getStyleByColumnAndRow($finalColumnStart, 1)->applyFromArray($style_border);
            $excel->getActiveSheet()->getStyleByColumnAndRow($finalColumnStart, 1)->applyFromArray($style_head_fill);
            $excel->getActiveSheet()->mergeCells( $excel->num2alpha($finalColumnStart-1).'1:' .$excel->num2alpha($finalColumnNum-1).'1');
        }

		$r = 2;

        $count = 0;
        $rowNum = 'odd';

		$dataStudents = array('gibbonCourseClassID' => $gibbonCourseClassID);
		$sqlStudents = "SELECT title, surname, preferredName, gibbonPerson.gibbonPersonID, dateStart FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Student' AND gibbonCourseClassID=:gibbonCourseClassID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') ORDER BY surname, preferredName";
		$resultStudents = $pdo->executeQuery($dataStudents, $sqlStudents);
        if ($resultStudents->rowCount() < 1) {
			$excel->getActiveSheet()->setCellValueByColumnAndRow(1, 3, __('There are no records to display.'));
            $excel->getActiveSheet()->getStyleByColumnAndRow(1, 3)->applyFromArray($style_border);

        } else {
            while ($rowStudents = $resultStudents->fetch()) {
                $r++;
                ++$count;
				//Column A
				$excel->getActiveSheet()->setCellValueByColumnAndRow(1, $r, Format::name('', $rowStudents['preferredName'], $rowStudents['surname'], 'Student', true));
                $excel->getActiveSheet()->getStyleByColumnAndRow(1, $r)->applyFromArray($style_border);

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
                            $attainment = __($rowEntry['attainmentValue']);
                        }
                        if ($rowEntry['attainmentValue'] == 'Complete') {
                            $attainment = __('Com');
                        } elseif ($rowEntry['attainmentValue'] == 'Incomplete') {
                            $attainment = __('Inc');
                        }
						$excel->getActiveSheet()->setCellValueByColumnAndRow((2 + ($i * ($subColCount))), $r, htmlPrep($rowEntry['attainmentValue']));
                        $excel->getActiveSheet()->getStyleByColumnAndRow((2 + ($i * ($subColCount))), $r)->applyFromArray($style_border);
                        $excel->getActiveSheet()->getStyleByColumnAndRow((2 + ($i * ($subColCount))), $r)->getNumberFormat()->setFormatCode($markFormat);

                        $effort = '';
                        if ($rowEntry['effortValue'] != '') {
                            $effort = __($rowEntry['effortValue']);
                        }
                        if ($rowEntry['effortValue'] == 'Complete') {
                            $effort = __('Com');
                        } elseif ($rowEntry['effortValue'] == 'Incomplete') {
                            $effort = __('Inc');
                        }
 						if ($enableEffort == 'Y') {
                            $excel->getActiveSheet()->setCellValueByColumnAndRow((3 + ($i * ($subColCount))), $r, $rowEntry['effortValue']);
                            $excel->getActiveSheet()->getStyleByColumnAndRow((3 + ($i * ($subColCount))), $r)->applyFromArray($style_border);
                        }
                        $excel->getActiveSheet()->setCellValueByColumnAndRow((4 + ($i * ($subColCount))), $r, $rowEntry['comment']);
                        $excel->getActiveSheet()->getStyleByColumnAndRow((4 + ($i * ($subColCount))), $r)->applyFromArray($style_border);
                    } else { //Fill empty spaces
                        $excel->getActiveSheet()->setCellValueByColumnAndRow((2 + ($i * ($subColCount))), $r, '');
                        $excel->getActiveSheet()->getStyleByColumnAndRow((2 + ($i * ($subColCount))), $r)->applyFromArray($style_border);
                        $excel->getActiveSheet()->setCellValueByColumnAndRow((3 + ($i * ($subColCount))), $r, '');
                        $excel->getActiveSheet()->getStyleByColumnAndRow((3 + ($i * ($subColCount))), $r)->applyFromArray($style_border);
                        $excel->getActiveSheet()->setCellValueByColumnAndRow((4 + ($i * ($subColCount))), $r, '');
                        $excel->getActiveSheet()->getStyleByColumnAndRow((4 + ($i * ($subColCount))), $r)->applyFromArray($style_border);
                    }
                }

                // Output Overall Grades, if enabled
                if ($markbook->getSetting('enableColumnWeighting') == 'Y') {
                    $finalColumnNum = 1 + ($columns * ($subColCount));

                    // Add Term Grades, if enabled & available
                    $terms = $markbook->getCurrentTerms();
                    if ($markbook->getSetting('enableGroupByTerm') == 'Y' && !empty($terms)) {
                        foreach ($terms as $termCount => $term) {
                            $termAverage = number_format(round($markbook->getTermAverage($rowStudents['gibbonPersonID'], $term['gibbonSchoolYearTermID']), 2), 2).$markSuffix;
                            $excel->getActiveSheet()->setCellValueByColumnAndRow( $finalColumnNum, $r, $termAverage);
                            $excel->getActiveSheet()->getStyleByColumnAndRow($finalColumnNum, $r)->applyFromArray($style_border);
                            $excel->getActiveSheet()->getStyleByColumnAndRow($finalColumnNum, $r)->getNumberFormat()->setFormatCode($markFormat);
                            $finalColumnNum++;
                        }
                    }

                    // Cumulative Average
                    $cumulativeAverage = $markbook->getCumulativeAverage($rowStudents['gibbonPersonID']);
                    $cumulativeAverage = is_numeric($cumulativeAverage)
                        ? number_format(round($cumulativeAverage, 2),2).$markSuffix
                        : $cumulativeAverage.$markSuffix;
                    $excel->getActiveSheet()->setCellValueByColumnAndRow( $finalColumnNum, $r, $cumulativeAverage);
                    $excel->getActiveSheet()->getStyleByColumnAndRow($finalColumnNum, $r)->applyFromArray($style_border);
                    $excel->getActiveSheet()->getStyleByColumnAndRow($finalColumnNum, $r)->getNumberFormat()->setFormatCode($markFormat);
                    $finalColumnNum++;

                    if ($markbook->getSetting('enableTypeWeighting') == 'Y' && count($markbook->getGroupedMarkbookTypes('year')) > 0) {

                        foreach ($markbook->getGroupedMarkbookTypes('year') as $type) {
                            // Final Weighted Types
                            $typeAverage = number_format(round($markbook->getTypeAverage($rowStudents['gibbonPersonID'], 'final', $type), 2), 2).$markSuffix;
                            $excel->getActiveSheet()->setCellValueByColumnAndRow( $finalColumnNum, $r, $typeAverage);
                            $excel->getActiveSheet()->getStyleByColumnAndRow($finalColumnNum, $r)->applyFromArray($style_border);
                            $excel->getActiveSheet()->getStyleByColumnAndRow($finalColumnNum, $r)->getNumberFormat()->setFormatCode($markFormat);
                            $finalColumnNum++;
                        }

                        // Final Grade
                        $finalAverage = number_format(round($markbook->getFinalGradeAverage($rowStudents['gibbonPersonID']), 2), 2).$markSuffix;
                        $excel->getActiveSheet()->setCellValueByColumnAndRow( $finalColumnNum, $r, $finalAverage);
                        $excel->getActiveSheet()->getStyleByColumnAndRow($finalColumnNum, $r)->applyFromArray($style_border);
                        $excel->getActiveSheet()->getStyleByColumnAndRow($finalColumnNum, $r)->getNumberFormat()->setFormatCode($markFormat);
                    }
                }
            }
			$excel->exportWorksheet();
        }
    }
}
