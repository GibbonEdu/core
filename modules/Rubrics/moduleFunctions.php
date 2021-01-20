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

use Gibbon\Forms\Form;
use Gibbon\Module\Rubrics\Visualise;

function rubricEdit($guid, $connection2, $gibbonRubricID, $scaleName = '', $search = '', $filter2 = '')
{
    global $pdo;

    $output = false;

    $data = array('gibbonRubricID' => $gibbonRubricID);

    //Get rows, columns and cells
    $sqlRows = "SELECT * FROM gibbonRubricRow WHERE gibbonRubricID=:gibbonRubricID ORDER BY sequenceNumber";
    $resultRows = $pdo->executeQuery($data, $sqlRows);
    $rowCount = $resultRows->rowCount();

    $sqlColumns = "SELECT * FROM gibbonRubricColumn WHERE gibbonRubricID=:gibbonRubricID ORDER BY sequenceNumber";
    $resultColumns = $pdo->executeQuery($data, $sqlColumns);
    $columnCount = $resultColumns->rowCount();

    $sqlCells = "SELECT * FROM gibbonRubricCell WHERE gibbonRubricID=:gibbonRubricID";
    $resultCells = $pdo->executeQuery($data, $sqlCells);
    $cellCount = $resultCells->rowCount();

    $sqlGradeScales = "SELECT gibbonScaleGrade.gibbonScaleGradeID, gibbonScaleGrade.* FROM gibbonRubricColumn
        JOIN gibbonScaleGrade ON (gibbonRubricColumn.gibbonScaleGradeID=gibbonScaleGrade.gibbonScaleGradeID)
        WHERE gibbonRubricColumn.gibbonRubricID=:gibbonRubricID";
    $resultGradeScales = $pdo->executeQuery($data, $sqlGradeScales);
    $gradeScales = ($resultGradeScales->rowCount() > 0)? $resultGradeScales->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE) : array();

    $sqlOutcomes = "SELECT gibbonOutcome.gibbonOutcomeID, gibbonOutcome.* FROM gibbonRubricRow
        JOIN gibbonOutcome ON (gibbonRubricRow.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID)
        WHERE gibbonRubricRow.gibbonRubricID=:gibbonRubricID";
    $resultOutcomes = $pdo->executeQuery($data, $sqlOutcomes);
    $outcomes = ($resultOutcomes->rowCount() > 0)? $resultOutcomes->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE) : array();

    if ($rowCount <= 0 or $columnCount <= 0) {
        $output .= "<div class='error'>";
        $output .= __('The rubric cannot be drawn.');
        $output .= '</div>';
    } else {
        $rows = $resultRows->fetchAll();
        $columns = $resultColumns->fetchAll();

        $cells = array();
        while ($rowCells = $resultCells->fetch()) {
            $cells[$rowCells['gibbonRubricRowID']][$rowCells['gibbonRubricColumnID']] = $rowCells;
        }

        $output .= "<div class='linkTop'>";
        $output .= "<a onclick='return confirm(\"".__('Are you sure you want to edit rows and columns? Any unsaved changes will be lost.')."\")' href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/rubrics_edit_editRowsColumns.php&gibbonRubricID=$gibbonRubricID&search=$search&filter2=$filter2'>".__('Edit Rows & Columns')."<img title='Edit' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/ style='margin: 0px 1px -4px 3px'></a>";
        $output .= '</div>';

        $form = Form::createTable('editRubric', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/rubrics_edit_editCellProcess.php?gibbonRubricID='.$gibbonRubricID.'&search='.$search.'&filter2='.$filter2);

        $form->setClass('rubricTable fullWidth');
        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

        $row = $form->addRow()->addClass();
            $row->addContent()->addClass('rubricCellEmpty');

        // Column Headers
        for ($n = 0; $n < $columnCount; ++$n) {
            $col = $row->addColumn()->addClass('rubricHeading column'.$columns[$n]['gibbonRubricColumnID']);

            // Display grade scale, otherwise column title
            if (!empty($gradeScales[$columns[$n]['gibbonScaleGradeID']])) {
                $gradeScaleGrade = $gradeScales[$columns[$n]['gibbonScaleGradeID']];
                $col->addContent('<b>'.$gradeScaleGrade['descriptor'].'</b>')
                    ->append(' ('.$gradeScaleGrade['value'].')')
                    ->append('<br/><span class="small emphasis">'.__($scaleName).' '.__('Scale').'</span>');
            } else {
                $col->addContent($columns[$n]['title'])->wrap('<b>', '</b>');
            }

            $col->addContent("<a onclick='return confirm(\"".__('Are you sure you want to delete this column? Any unsaved changes will be lost.')."\")' href='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/rubrics_edit_deleteColumnProcess.php?gibbonRubricID=$gibbonRubricID&gibbonRubricColumnID=".$columns[$n]['gibbonRubricColumnID'].'&address='.$_GET['q']."&search=$search&filter2=$filter2'><img title='".__('Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/ style='margin: 2px 0px 0px 0px'></a>");
        }

        // Rows
        $count = 0;
        for ($i = 0; $i < $rowCount; ++$i) {
            $row = $form->addRow();
            $col = $row->addColumn()->addClass('rubricHeading row'.$rows[$i]['gibbonRubricRowID']);

            // Row Header
            if (!empty($outcomes[$rows[$i]['gibbonOutcomeID']])) {
                $outcome = $outcomes[$rows[$i]['gibbonOutcomeID']];
                $col->addContent('<b>'.__($outcome['name']).'</b>')
                    ->append(!empty($outcome['category'])? ('<i> - <br/>'.$outcome['category'].'</i>') : '')
                    ->append('<br/><span class="small emphasis">'.$outcome['scope'].' '.__('Outcome').'</span>');
                $rows[$i]['title'] = $outcome['name'];
            } else {
                $col->addContent($rows[$i]['title'])->wrap('<b>', '</b>');
            }

            $col->addContent("<a onclick='return confirm(\"".__('Are you sure you want to delete this row? Any unsaved changes will be lost.')."\")' href='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/rubrics_edit_deleteRowProcess.php?gibbonRubricID=$gibbonRubricID&gibbonRubricRowID=".$rows[$i]['gibbonRubricRowID'].'&address='.$_GET['q']."&search=$search&filter2=$filter2'><img title='".__('Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/ style='margin: 2px 0px 0px 0px'></a><br/>");

            for ($n = 0; $n < $columnCount; ++$n) {
                $cell = @$cells[$rows[$i]['gibbonRubricRowID']][$columns[$n]['gibbonRubricColumnID']];
                $row->addTextArea("cell[$count]")->setValue(isset($cell['contents'])? $cell['contents']: '')->setClass('rubricCell rubricCellEdit');

                $form->addHiddenValue("gibbonRubricCellID[$count]", isset($cell['gibbonRubricCellID'])? $cell['gibbonRubricCellID']: '');
                $form->addHiddenValue("gibbonRubricColumnID[$count]", $columns[$n]['gibbonRubricColumnID']);
                $form->addHiddenValue("gibbonRubricRowID[$count]", $rows[$i]['gibbonRubricRowID']);

                $count++;
            }
        }

        $row = $form->addRow();
            $row->addSubmit();

        $output .= $form->getOutput();

        $output .= "<style>";
        for ($i = 0; $i < $rowCount; ++$i) {
            $color = $rows[$i]['backgroundColor'] ?? '#ffffff';
            $colorValue = hexdec(substr($color, 1, 2)) + hexdec(substr($color, 3, 2)) + hexdec(substr($color, 5, 2));
            $textColor = $colorValue > 580 ? '#5b5757' : '#5b5757';
            $output .= ".row".$rows[$i]['gibbonRubricRowID'].'{ background-color: '.$color.'; color: '.$textColor.'; } ';
        }
        for ($i = 0; $i < $columnCount; ++$i) {
            $color = $columns[$i]['backgroundColor'] ?? '#ffffff';
            $colorValue = hexdec(substr($color, 1, 2)) + hexdec(substr($color, 3, 2)) + hexdec(substr($color, 5, 2));
            $textColor = empty($colorValue) || $colorValue > 450 ? '#5b5757' : '#ffffff';
            $output .= ".column".$columns[$i]['gibbonRubricColumnID'].'{ background-color: '.$color.'; color: '.$textColor.'; } ';
        }
        $output .= "</style>";
    }

    return $output;
}

//If $mark=TRUE, then marking tools are made available, otherwise it is view only
function rubricView($guid, $connection2, $gibbonRubricID, $mark, $gibbonPersonID = '', $contextDBTable = '', $contextDBTableIDField = '', $contextDBTableID = '', $contextDBTableGibbonRubricIDField = '', $contextDBTableNameField = '', $contextDBTableDateField = '')
{
    global $pdo, $page, $gibbon;

    $output = false;
    $hasContexts = $contextDBTable != '' and $contextDBTableIDField != '' and $contextDBTableID != '' and $contextDBTableGibbonRubricIDField != '' and $contextDBTableNameField != '' and $contextDBTableDateField != '';

    
        $data = array('gibbonRubricID' => $gibbonRubricID);
        $sql = 'SELECT * FROM gibbonRubric WHERE gibbonRubricID=:gibbonRubricID';
        $result = $connection2->prepare($sql);
        $result->execute($data);

    if ($result->rowCount() != 1) {
        echo "<div class='error'>";
        echo __('The specified record cannot be found.');
        echo '</div>';
    } else {
        $values = $result->fetch();

        //Get rows, columns and cells
        $sqlRows = "SELECT * FROM gibbonRubricRow WHERE gibbonRubricID=:gibbonRubricID ORDER BY sequenceNumber";
        $resultRows = $pdo->executeQuery($data, $sqlRows);
        $rowCount = $resultRows->rowCount();

        $sqlColumns = "SELECT * FROM gibbonRubricColumn WHERE gibbonRubricID=:gibbonRubricID ORDER BY sequenceNumber";
        $resultColumns = $pdo->executeQuery($data, $sqlColumns);
        $columnCount = $resultColumns->rowCount();

        $sqlCells = "SELECT * FROM gibbonRubricCell WHERE gibbonRubricID=:gibbonRubricID";
        $resultCells = $pdo->executeQuery($data, $sqlCells);
        $cellCount = $resultCells->rowcount();

        $sqlGradeScales = "SELECT gibbonScaleGrade.gibbonScaleGradeID, gibbonScaleGrade.*, gibbonScale.name FROM gibbonRubricColumn
            JOIN gibbonScaleGrade ON (gibbonRubricColumn.gibbonScaleGradeID=gibbonScaleGrade.gibbonScaleGradeID)
            JOIN gibbonScale ON (gibbonScale.gibbonScaleID=gibbonScaleGrade.gibbonScaleID)
            WHERE gibbonRubricColumn.gibbonRubricID=:gibbonRubricID";
        $resultGradeScales = $pdo->executeQuery($data, $sqlGradeScales);
        $gradeScales = ($resultGradeScales->rowCount() > 0)? $resultGradeScales->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE) : array();

        $sqlOutcomes = "SELECT gibbonOutcome.gibbonOutcomeID, gibbonOutcome.* FROM gibbonRubricRow
            JOIN gibbonOutcome ON (gibbonRubricRow.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID)
            WHERE gibbonRubricRow.gibbonRubricID=:gibbonRubricID";
        $resultOutcomes = $pdo->executeQuery($data, $sqlOutcomes);
        $outcomes = ($resultOutcomes->rowCount() > 0)? $resultOutcomes->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE) : array();

        // Check if outcomes are specified in unit
        $unitOutcomes = array();
        if ($hasContexts) {
            $dataUnitOutcomes = array();
            $sqlUnitOutcomes = "SHOW COLUMNS FROM `$contextDBTable` LIKE 'gibbonUnitID'";
            $resultUnitOutcomes = $pdo->executeQuery($dataUnitOutcomes, $sqlUnitOutcomes);

            if ($resultUnitOutcomes->rowCount() > 0) {
                $dataUnitOutcomes = array('gibbonRubricID' => $gibbonRubricID, 'contextDBTableID' => $contextDBTableID);
                $sqlUnitOutcomes = "SELECT gibbonUnitOutcome.gibbonOutcomeID, gibbonUnitOutcome.gibbonUnitOutcomeID FROM gibbonRubricRow
                    JOIN gibbonOutcome ON (gibbonRubricRow.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID)
                    JOIN gibbonUnitOutcome ON (gibbonUnitOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID)
                    JOIN `$contextDBTable` ON (`$contextDBTable`.gibbonUnitID=gibbonUnitOutcome.gibbonUnitID AND `$contextDBTableIDField`=:contextDBTableID)
                    WHERE gibbonRubricRow.gibbonRubricID=:gibbonRubricID";
                $resultUnitOutcomes = $pdo->executeQuery($dataUnitOutcomes, $sqlUnitOutcomes);
                $unitOutcomes = ($resultUnitOutcomes->rowCount() > 0)? $resultUnitOutcomes->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE) : array();
            }
        }

        // Load rubric data for this student
        $dataEntries = array('gibbonRubricID' => $gibbonRubricID, 'gibbonPersonID' => $gibbonPersonID, 'contextDBTable' => $contextDBTable, 'contextDBTableID' => $contextDBTableID);
        $sqlEntries = "SELECT gibbonRubricEntry.gibbonRubricCellID, gibbonRubricEntry.* FROM gibbonRubricCell
            LEFT JOIN gibbonRubricEntry ON (gibbonRubricEntry.gibbonRubricCellID=gibbonRubricCell.gibbonRubricCellID)
            WHERE gibbonRubricCell.gibbonRubricID=:gibbonRubricID
            AND gibbonRubricEntry.gibbonPersonID=:gibbonPersonID
            AND gibbonRubricEntry.contextDBTable=:contextDBTable
            AND gibbonRubricEntry.contextDBTableID=:contextDBTableID";
        $resultEntries = $pdo->executeQuery($dataEntries, $sqlEntries);
        $entries = ($resultEntries->rowCount() > 0)? $resultEntries->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE) : array();


        if ($rowCount <= 0 or $columnCount <= 0) {
            $output .= "<div class='error'>";
            $output .= __('The rubric cannot be drawn.');
            $output .= '</div>';
        } else {
            $rows = $resultRows->fetchAll();
            $columns = $resultColumns->fetchAll();

            $cells = array();
            while ($rowCells = $resultCells->fetch()) {
                $cells[$rowCells['gibbonRubricRowID']][$rowCells['gibbonRubricColumnID']] = $rowCells;
            }

            //Get other uses of this rubric in this context, and store for use in visualisation
            $contexts = array();
            if ($hasContexts) {
                $dataContext = array('gibbonPersonID' => $gibbonPersonID);
                $sqlContext = "SELECT gibbonRubricEntry.*, $contextDBTable.*, gibbonRubricEntry.*, gibbonRubricCell.*, gibbonCourse.nameShort AS course, gibbonCourseClass.nameshort AS class
                    FROM gibbonRubricEntry
                    JOIN $contextDBTable ON (gibbonRubricEntry.contextDBTableID=$contextDBTable.$contextDBTableIDField
                        AND gibbonRubricEntry.gibbonRubricID=$contextDBTable.$contextDBTableGibbonRubricIDField)
                    JOIN gibbonRubricCell ON (gibbonRubricEntry.gibbonRubricCellID=gibbonRubricCell.gibbonRubricCellID)
                    LEFT JOIN gibbonCourseClass ON ($contextDBTable.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                    LEFT JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                    WHERE contextDBTable='$contextDBTable'
                    AND gibbonRubricEntry.gibbonPersonID=:gibbonPersonID
                    AND NOT $contextDBTableDateField IS NULL
                    ORDER BY $contextDBTableDateField DESC";
                $resultContext = $pdo->executeQuery($dataContext,  $sqlContext);

                if ($resultContext->rowCount() > 0) {
                    while ($rowContext = $resultContext->fetch()) {
                        $context = $rowContext['course'].'.'.$rowContext['class'].' - '.$rowContext[$contextDBTableNameField].' ('.dateConvertBack($guid, $rowContext[$contextDBTableDateField]).')';
                        $cells[$rowContext['gibbonRubricRowID']][$rowContext['gibbonRubricColumnID']]['context'][] = $context;

                        array_push($contexts, array('gibbonRubricEntry' => $rowContext['gibbonRubricEntry'], 'gibbonRubricID' => $rowContext['gibbonRubricID'], 'gibbonPersonID' => $rowContext['gibbonPersonID'], 'gibbonRubricCellID' => $rowContext['gibbonRubricCellID'], 'contextDBTable' => $rowContext['contextDBTable'], 'contextDBTableID' => $rowContext['contextDBTableID']));
                    }
                }
            }

            //Controls for viewing mode
            if ($gibbonPersonID != '') {
                $output .= "<div class='linkTop'>";
                $output .= "Viewing Mode: <select name='type' id='type' class='type' style='width: 152px; float: none'>";
                $output .= "<option id='type' name='type' value='Current'>".__('Current').'</option>';
                $output .= "<option id='type' name='type' value='Visualise'>".__('Visualise').'</option>';
                $output .= "<option id='type' name='type' value='Historical'>".__('Historical Data').'</option>';
                $output .= '</select>';
                $output .= '</div>';
            }

            //Div to contain rubric for current and historicla views
            $output .= "<div id='rubric'>";

                if ($mark == true) {
                    $output .= '<p>';
                    $output .= __('Click on any of the cells below to highlight them. Data is saved automatically after each click.');
                    $output .= '</p>';
                }

                $form = Form::createTable('viewRubric', $_SESSION[$guid]['absoluteURL'].'/index.php');
                $form->setClass('rubricTable fullWidth');

                $row = $form->addRow()->addClass();
                    $row->addContent()->addClass('');

                if ($hasContexts) {
                    $form->toggleVisibilityByClass('currentView')->onSelect('type')->when('Current');
                    $form->toggleVisibilityByClass('historical')->onSelect('type')->when('Historical');
                }

                    // Column Headers
                    for ($n = 0; $n < $columnCount; ++$n) {
                        $column = $row->addColumn()->addClass('rubricHeading column'.$columns[$n]['gibbonRubricColumnID']);

                        // Display grade scale, otherwise column title
                        if (!empty($gradeScales[$columns[$n]['gibbonScaleGradeID']])) {
                            $gradeScaleGrade = $gradeScales[$columns[$n]['gibbonScaleGradeID']];
                            $column->addContent('<b>'.$gradeScaleGrade['descriptor'].'</b>')
                                ->append(' ('.$gradeScaleGrade['value'].')')
                                ->append('<br/><span class="small emphasis">'.__($gradeScaleGrade['name']).' '.__('Scale').'</span>');
                        } else {
                            $column->addContent($columns[$n]['title'])->wrap('<b>', '</b>');
                        }
                    }

                    // Rows
                    $count = 0;
                    for ($i = 0; $i < $rowCount; ++$i) {
                        $row = $form->addRow();
                        $col = $row->addColumn()->addClass('rubricHeading rubricRowHeading row'.$rows[$i]['gibbonRubricRowID']);

                        // Row Header
                        if (!empty($outcomes[$rows[$i]['gibbonOutcomeID']])) {
                            $outcome = $outcomes[$rows[$i]['gibbonOutcomeID']];
                            $content = $col->addContent('<b>'.__($outcome['name']).'</b>')
                                ->append(!empty($outcome['category'])? ('<i> - <br/>'.$outcome['category'].'</i>') : '')
                                ->append('<br/><span class="small emphasis">'.$outcome['scope'].' '.__('Outcome').'</span>')
                                ->wrap('<span title="'.$outcome['description'].'">', '</span>');
                            // Highlight unit outcomes with a checkmark
                            if (isset($unitOutcomes[$rows[$i]['gibbonOutcomeID']])) {
                                $content->append('<img style="float: right" title="'.__('This outcome is one of the unit outcomes.').'" src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/iconTick.png"/>');
                            }
                            $rows[$i]['title'] = $outcomes[$rows[$i]['gibbonOutcomeID']]['name'];
                            $rows[$i]['title'];
                        } else {
                            $col->addContent($rows[$i]['title'])->wrap('<b>', '</b>');
                        }

                        // Cells
                        for ($n = 0; $n < $columnCount; ++$n) {
                            if (!isset($cells[$rows[$i]['gibbonRubricRowID']][$columns[$n]['gibbonRubricColumnID']])) {
                                $row->addColumn()->addClass('rubricCell');
                                continue;
                            }

                            $cell = $cells[$rows[$i]['gibbonRubricRowID']][$columns[$n]['gibbonRubricColumnID']];

                            $highlightClass = isset($entries[$cell['gibbonRubricCellID']])? 'rubricCellHighlight' : '';
                            $markableClass = ($mark == true)? 'markableCell' : '';

                            $col = $row->addColumn()->addClass('rubricCell '.$highlightClass);
                                $col->addContent($cell['contents'])
                                    ->addClass('currentView '.$markableClass)
                                    ->append('<span class="cellID" data-cell="'.$cell['gibbonRubricCellID'].'"></span>');

                            // Add historical contexts if applicable, shown/hidden by dropdown
                            $countHistorical = isset($cell['context']) ? count($cell['context']) : 0;
                            if ($hasContexts && $countHistorical > 0) {
                                $historicalContent = '';
                                for ($h = 0; $h < min(7, $countHistorical); ++$h) {
                                    $historicalContent .= ($h + 1) . ') ' . $cell['context'][$h] . '<br/>';
                                }

                                $col->addContent($historicalContent)
                                    ->addClass('historical')
                                    ->prepend('<b><u>' . __('Total Occurences:') . ' ' . $countHistorical . '</u></b><br/>')
                                    ->append(($countHistorical > 7)? '<b>'.__('Older occurrences not shown...').'</b>' : '')
                                    ->append('<span class="cellID" data-cell="' . $cell['gibbonRubricCellID'] . '"></span>');
                            }
                        }
                    }

                    if ($mark == true) {
                        $output .= "<script type='text/javascript'>";
                        $output .= '$(document).ready(function(){';
                        $output .= '$(".markableCell").parent().click(function(){';
                            $output .= "var mode = '';";
                            $output .= "var cellID = $(this).find('.cellID').data('cell');";
                            $output .= "if ($(this).hasClass('rubricCellHighlight') == false ) {";
                                $output .= "$(this).addClass('rubricCellHighlight');";
                                $output .= "mode = 'Add';";
                            $output .= '} else {';
                                $output .= "$(this).removeClass('rubricCellHighlight');";
                                $output .= "mode = 'Remove';";
                            $output .= '}';
                            $output .= 'var request=$.ajax({ url: "'.$_SESSION[$guid]['absoluteURL'].'/modules/Rubrics/rubrics_data_saveAjax.php", type: "GET", data: {mode: mode, gibbonRubricID : "' . $gibbonRubricID.'", gibbonPersonID : "'.$gibbonPersonID.'", gibbonRubricCellID : cellID, contextDBTable : "'.$contextDBTable.'",contextDBTableID : "'.$contextDBTableID.'"}, dataType: "html"});';
                            $output .= '});';
                        $output .= '});';
                        $output .= '</script>';
                    }


                $output .= $form->getOutput();

            $output .= "</div>";

            //Div to contain visualisation
            $output .= "<div id='visualise' style='display: none'>";
                $output .= "<p>";
                    $output .= __("This view offers a visual representation of all rubric data for the current student, this year, in the current context:");
                $output .= "</p>";

                require_once __DIR__ . '/src/Visualise.php';
                $visualise = new Visualise($gibbon->session->get('absoluteURL'), $page, $gibbonPersonID, $columns, $rows, $cells, $contexts);

                $output .= $visualise->renderVisualise();

            $output .= "</div>";

            //Function to show/hide rubric/visualisation
            $output .= "<script type='text/javascript'>
                 $(document).ready(function(){
                    $('#type').change(function () {
                        if ($(this).val() == 'Current' || $(this).val() == 'Historical') {
                            $('#rubric').slideDown('fast', $('#rubric').css('display','block'));
                            $('#visualise').css('display','none');
                        } else {
                            $('#visualise').slideDown('fast', $('#visualise').css('display','block'));
                            $('#rubric').css('display','none');
                        }
                    });
                });
            </script>";

            $output .= "<style>";
            for ($i = 0; $i < $rowCount; ++$i) {
                $color = $rows[$i]['backgroundColor'] ?? '#666666';
                $color = $color == '#ffffff' ? '#666666' : $color;
                $colorValue = hexdec(substr($color, 1, 2)) + hexdec(substr($color, 3, 2)) + hexdec(substr($color, 5, 2));
                $textColor = $colorValue > 580 ? '#5b5757' : '#ffffff';
                $output .= ".row".$rows[$i]['gibbonRubricRowID'].'{ background-color: '.$color.'; color: '.$textColor.'; } ';
            }
            for ($i = 0; $i < $columnCount; ++$i) {
                $color = $columns[$i]['backgroundColor'] ?? '#ffffff';
                $colorValue = hexdec(substr($color, 1, 2)) + hexdec(substr($color, 3, 2)) + hexdec(substr($color, 5, 2));
                $textColor = empty($colorValue) || $colorValue > 450 ? '#5b5757' : '#ffffff';
                $output .= ".column".$columns[$i]['gibbonRubricColumnID'].'{ background-color: '.$color.'; color: '.$textColor.'; } ';
            }
            $output .= "</style>";
        }

        // Append the Rubric stylesheet to the current page - for Markbook view of Rubric (only if it's not already included)
        $output .= '<script>';
        $output .= "if (!$('link[href*=\"./modules/Rubrics/css/module.css\"]').length) {";
        $output .= "$('<link>').appendTo('head').attr({type: 'text/css', rel: 'stylesheet', href: './modules/Rubrics/css/module.css'})";
        $output .= '}';
        $output .= '</script>';
    }

    return $output;
}
