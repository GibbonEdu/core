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
        $output .= __($guid, 'The rubric cannot be drawn.');
        $output .= '</div>';
    } else {
        $rows = $resultRows->fetchAll();
        $columns = $resultColumns->fetchAll();

        $cells = array();
        while ($rowCells = $resultCells->fetch()) {
            $cells[$rowCells['gibbonRubricRowID']][$rowCells['gibbonRubricColumnID']] = $rowCells;
        }

        $output .= '<style type="text/css">';
        $output .= 'table.rubric { width: 100%; border-collapse: collapse; border: 1px solid #000 }';
        $output .= 'table.rubric tr { border: 1px solid #000 }';
        $output .= 'table.rubric td { border: 1px solid #000 }';

        $output .= '.rubricTable { border-collapse: collapse; }';
        $output .= '.rubricHeader { border: 1px solid #000; width: 100px; }';
        $output .= 'table.smallIntBorder td.rubricCell { border: 1px solid #000; padding: 0px !important; }';
        $output .= '.rubricCell textarea { background-color: #fff; border: 0px solid #000; margin: 0px; font-size: 85%; width: 100%; height: 100px; }';

        $output .= '</style>';

        $output .= "<div class='linkTop'>";
        $output .= "<a onclick='return confirm(\"Are you sure you want to edit rows and columns? Any unsaved changes will be lost.\")' href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/rubrics_edit_editRowsColumns.php&gibbonRubricID=$gibbonRubricID&search=$search&filter2=$filter2'>Edit Rows & Columns<img title='Edit' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/ style='margin: 0px 1px -4px 3px'></a>";
        $output .= '</div>';

        $form = Form::create('editRubric', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/rubrics_edit_editCellProcess.php?gibbonRubricID='.$gibbonRubricID.'&search='.$search.'&filter2='.$filter2);

        $form->addClass('rubricTable');
        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

        $row = $form->addRow()->addClass('head');
            $row->addContent();
            
        // Column Headers
        for ($n = 0; $n < $columnCount; ++$n) {
            $column = $row->addColumn()->addClass('rubricHeader');

            // Display grade scale, otherwise column title
            if ($columns[$n]['gibbonScaleGradeID'] != '') {
                $gradeScaleGrade = $gradeScales[$columns[$n]['gibbonScaleGradeID']];
                $column->addContent($gradeScaleGrade['descriptor'])
                    ->append(' ('.$gradeScaleGrade['value'].')')
                    ->append('<br/><span class="small emphasis">'.__($scaleName).' '.__('Scale').'</span>')
                    ->wrap('<b>', '</b>');
            } else {
                $column->addContent($columns[$n]['title'])->wrap('<b>', '</b>');
            }

            $column->addContent("<a onclick='return confirm(\"".__($guid, 'Are you sure you want to delete this column? Any unsaved changes will be lost.')."\")' href='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/rubrics_edit_deleteColumnProcess.php?gibbonRubricID=$gibbonRubricID&gibbonRubricColumnID=".$columns[$n]['gibbonRubricColumnID'].'&address='.$_GET['q']."&search=$search&filter2=$filter2'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/ style='margin: 2px 0px 0px 0px'></a>");
        }

        // Rows
        $count = 0;
        for ($i = 0; $i < $rowCount; ++$i) {
            $row = $form->addRow();
            $column = $row->addColumn()->addClass('rubricHeader');

            // Row Header
            if ($rows[$i]['gibbonOutcomeID'] != '') {
                $outcome = $outcomes[$rows[$i]['gibbonOutcomeID']];
                $column->addContent('<b>'.__($outcome['name']).'</b>')
                    ->append(!empty($outcome['category'])? ('<i> - <br/>'.$outcome['category'].'</i>') : '')
                    ->append('<br/><span class="small emphasis">'.$outcome['scope'].' '.__('Outcome').'</span>');
            } else {
                $column->addContent($rows[$i]['title'])->wrap('<b>', '</b>');
            }

            $column->addContent("<a onclick='return confirm(\"".__($guid, 'Are you sure you want to delete this row? Any unsaved changes will be lost.')."\")' href='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/rubrics_edit_deleteRowProcess.php?gibbonRubricID=$gibbonRubricID&gibbonRubricRowID=".$rows[$i]['gibbonRubricRowID'].'&address='.$_GET['q']."&search=$search&filter2=$filter2'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/ style='margin: 2px 0px 0px 0px'></a><br/>");

            for ($n = 0; $n < $columnCount; ++$n) {
                $cell = $cells[$rows[$i]['gibbonRubricRowID']][$columns[$n]['gibbonRubricColumnID']];
                $row->addTextArea("cell[$count]")->setValue(isset($cell['contents'])? $cell['contents']: '')->setClass('rubricCell');

                $form->addHiddenValue("gibbonRubricCellID[$count]", isset($cell['gibbonRubricCellID'])? $cell['gibbonRubricCellID']: '');
                $form->addHiddenValue("gibbonRubricColumnID[$count]", $columns[$n]['gibbonRubricColumnID']);
                $form->addHiddenValue("gibbonRubricRowID[$count]", $rows[$i]['gibbonRubricRowID']);

                $count++;
            }
        }

        $row = $form->addRow();
            $row->addSubmit();
        
        $output .= $form->getOutput();
    }

    return $output;
}

//If $mark=TRUE, then marking tools are made available, otherwise it is view only
function rubricView($guid, $connection2, $gibbonRubricID, $mark, $gibbonPersonID = '', $contextDBTable = '', $contextDBTableIDField = '', $contextDBTableID = '', $contextDBTableGibbonRubricIDField = '', $contextDBTableNameField = '', $contextDBTableDateField = '')
{
    global $pdo;
    
    $output = false;

    try {
        $data = array('gibbonRubricID' => $gibbonRubricID);
        $sql = 'SELECT * FROM gibbonRubric WHERE gibbonRubricID=:gibbonRubricID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($result->rowCount() != 1) {
        echo "<div class='error'>";
        echo __($guid, 'The specified record cannot be found.');
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

        if ($rowCount <= 0 or $columnCount <= 0) {
            $output .= "<div class='error'>";
            $output .= __($guid, 'The rubric cannot be drawn.');
            $output .= '</div>';
        } else {
            $rows = $resultRows->fetchAll();
            $columns = $resultColumns->fetchAll();

            $cells = array();
            while ($rowCells = $resultCells->fetch()) {
                $cells[$rowCells['gibbonRubricRowID']][$rowCells['gibbonRubricColumnID']] = $rowCells;
            }

            //Get other uses of this rubric in this context
            $contexts = array();
            $contextCount = 0;
            if ($contextDBTable != '' and $contextDBTableIDField != '' and $contextDBTableID != '' and $contextDBTableGibbonRubricIDField != '' and $contextDBTableNameField != '' and $contextDBTableDateField != '') {
                try {
                    $dataContext = array('gibbonPersonID' => $gibbonPersonID);
                    $sqlContext = "SELECT gibbonRubricEntry.*, $contextDBTable.*, gibbonRubricEntry.*, gibbonRubricCell.*, gibbonCourse.nameShort AS course, gibbonCourseClass.nameshort AS class FROM gibbonRubricEntry JOIN $contextDBTable ON (gibbonRubricEntry.contextDBTableID=$contextDBTable.$contextDBTableIDField AND gibbonRubricEntry.gibbonRubricID=$contextDBTable.$contextDBTableGibbonRubricIDField) JOIN gibbonRubricCell ON (gibbonRubricEntry.gibbonRubricCellID=gibbonRubricCell.gibbonRubricCellID) LEFT JOIN gibbonCourseClass ON ($contextDBTable.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) LEFT JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE contextDBTable='$contextDBTable' AND gibbonRubricEntry.gibbonPersonID=:gibbonPersonID AND NOT $contextDBTableDateField IS NULL ORDER BY $contextDBTableDateField DESC";
                    $resultContext = $connection2->prepare($sqlContext);
                    $resultContext->execute($dataContext);
                } catch (PDOException $e) {
                }
                while ($rowContext = $resultContext->fetch()) {
                    $context = $rowContext['course'].'.'.$rowContext['class'].' - '.$rowContext[$contextDBTableNameField].' ('.dateConvertBack($guid, $rowContext[$contextDBTableDateField]).')<br/>';
                    if (isset($cells[$rowContext['gibbonRubricRowID']][$rowContext['gibbonRubricColumnID']]['context'])) {
                        $cells[$rowContext['gibbonRubricRowID']][$rowContext['gibbonRubricColumnID']]['context'] .= $context;
                    } else {
                        $cells[$rowContext['gibbonRubricRowID']][$rowContext['gibbonRubricColumnID']]['context'] = $context;
                    }
                }
            }

            if ($mark == true) {
                echo '<p>';
                echo __($guid, 'Click on any of the cells below to highlight them. Data is saved automatically after each click.');
                echo '</p>';
            }

            //Controls for viewing mode
            if ($gibbonPersonID != '') {
                $output .= "<script type='text/javascript'>";
                $output .= '$(document).ready(function(){';
                $output .= "$('div.historical').css('display','none');";

                $output .= "$('#type').change(function(){";
                $output .= "if ($('#type').val()=='Current' ) {";
                $output .= "$('div.historical').css('display','none');";
                $output .= "$('div.currentView').css('display','block');";
                $output .= '} ';
                $output .= "else if ($('#type').val()=='Historical' ) {";
                $output .= "$('div.currentView').css('display','none');";
                $output .= "$('div.historical').css('display','block');";
                $output .= '}';
                $output .= '});';
                $output .= '});';
                $output .= '</script>';
                $output .= "<div class='linkTop'>";
                $output .= "Viewing Mode: <select name='type' id='type' class='type' style='width: 152px; float: none'>";
                $output .= "<option id='type' name='type' value='Current'>".__($guid, 'Current').'</option>';
                $output .= "<option id='type' name='type' value='Historical'>".__($guid, 'Historical Data').'</option>';
                $output .= '</select>';
                $output .= '</div>';
            }

            $output .= '<style type="text/css">';
            $output .= 'table.rubric { width: 100%; border-collapse: collapse; border: 1px solid #000 }';
            $output .= 'table.rubric tr { border: 1px solid #000 }';
            $output .= 'table.rubric td { border: 1px solid #000 }';
            $output .= '</style>';

            $output .= "<form method='post' action='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/rubrics_data_editProcess.php?gibbonRubricID=$gibbonRubricID&gibbonPersonID=$gibbonPersonID'>";
            $output .= "<table cellspacing='0' class='rubric'>";
			//Create header
			$output .= "<tr class='head'>";
            $output .= "<td style='width: 100px; background: none; background-color: #ffffff; border-left: 1px solid #fff; border-top: 1px solid #fff'></td>";
            for ($n = 0; $n < $columnCount; ++$n) {
                $output .= "<td style='vertical-align: bottom'>";
                if ($columns[$n]['gibbonScaleGradeID'] != '') {
                    try {
                        $dataOutcome = array('gibbonScaleGradeID' => $columns[$n]['gibbonScaleGradeID']);
                        $sqlOutcome = 'SELECT * FROM gibbonScaleGrade WHERE gibbonScaleGradeID=:gibbonScaleGradeID';
                        $resultOutcome = $connection2->prepare($sqlOutcome);
                        $resultOutcome->execute($dataOutcome);
                    } catch (PDOException $e) {
                    }
                    if ($resultOutcome->rowCount() != 1) {
                        echo __($guid, 'Error');
                    } else {
                        $rowOutcome = $resultOutcome->fetch();
                        $output .= '<b>'.__($guid, $rowOutcome['descriptor']).' ('.__($guid, $rowOutcome['value']).')</b><br/>';
						//Try to get scale name
						if ($values['gibbonScaleID'] != '') {
							try {
								$dataScale = array('gibbonScaleID' => $values['gibbonScaleID']);
								$sqlScale = 'SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID';
								$resultScale = $connection2->prepare($sqlScale);
								$resultScale->execute($dataScale);
							} catch (PDOException $e) {
								echo "<div class='error'>".$e->getMessage().'</div>';
							}

							if ($resultScale->rowCount() == 1) {
								$rowScale = $resultScale->fetch();
							}
						}
                        if ($rowScale['name'] != '') {
                            $output .= "<span style='font-size: 85%'><i>".__($guid, $rowScale['name']).' Scale</span><br/>';
                        }
                    }
                } else {
                    $output .= '<b>'.$columns[$n]['title'].'</b><br/>';
                }
                $output .= '</td>';
            }
            $output .= '</tr>';

			//Create body
			for ($i = 0; $i < $rowCount; ++$i) {
				$output .= "<tr style='height: auto'>";
				$output .= "<td style='background: none!important; background-color: #666!important; color: #fff; vertical-align: top; padding: 0px!important'>";
				if ($rows[$i]['gibbonOutcomeID'] != '') {
					try {
						$dataOutcome = array('gibbonOutcomeID' => $rows[$i]['gibbonOutcomeID']);
						$sqlOutcome = 'SELECT * FROM gibbonOutcome WHERE gibbonOutcomeID=:gibbonOutcomeID';
						$resultOutcome = $connection2->prepare($sqlOutcome);
						$resultOutcome->execute($dataOutcome);
					} catch (PDOException $e) {
					}
					if ($resultOutcome->rowCount() != 1) {
						echo __($guid, 'Error');
					} else {
						$rowOutcome = $resultOutcome->fetch();

								//Check if outcome is specified in unit
								if ($contextDBTable != '' and $contextDBTableID != '' and $contextDBTableIDField != '') {
									try {
										$dataOutcome2 = array('gibbonOutcomeID' => $rows[$i]['gibbonOutcomeID'], 'contextDBTableID' => $contextDBTableID);
										$sqlOutcome2 = "SELECT * FROM gibbonOutcome JOIN gibbonUnitOutcome ON (gibbonUnitOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID) JOIN $contextDBTable ON ($contextDBTable.gibbonUnitID=gibbonUnitOutcome.gibbonUnitID) WHERE gibbonOutcome.gibbonOutcomeID=:gibbonOutcomeID AND $contextDBTableIDField=:contextDBTableID";
										$resultOutcome2 = $connection2->prepare($sqlOutcome2);
										$resultOutcome2->execute($dataOutcome2);
									} catch (PDOException $e) {
									}
									if ($resultOutcome2->rowCount()) {
										$output .= "<img style='float: right' title='This outcome is one of the unit outcomes.' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick.png'/> ";
									}
								}

						if ($rowOutcome['category'] == '') {
							$output .= "<span title='".htmlprep($rowOutcome['description'])."'><b>".$rowOutcome['name'].'</b></span><br/>';
						} else {
							$output .= "<span title='".htmlprep($rowOutcome['description'])."'><b>".$rowOutcome['name'].'</b><i> - '.$rowOutcome['category'].'</span><br/>';
						}
						$output .= "<span style='font-size: 85%'><i>".$rowOutcome['scope'].' '.__($guid, 'Outcome').'</span><br/>';
					}
				} else {
					$output .= '<b>'.$rows[$i]['title'].'</b><br/>';
				}
				$output .= '</td>';
				for ($n = 0; $n < $columnCount; ++$n) {
					if ($mark == true) {
						$output .= "<script type='text/javascript'>";
						$output .= '$(document).ready(function(){';
						$output .= '$("#'.$rows[$i]['gibbonRubricRowID'].'-'.$columns[$n]['gibbonRubricColumnID'].'").click(function(){';
						$output .= 'if ($("#'.$rows[$i]['gibbonRubricRowID'].'-'.$columns[$n]['gibbonRubricColumnID']."\").css('background-color')==\"rgb(251, 251, 251)\" ) {";
						$output .= '$("#'.$rows[$i]['gibbonRubricRowID'].'-'.$columns[$n]['gibbonRubricColumnID']."\").css('background', 'none').css('background-color', '#79FA74');";
						$output .= 'var request=$.ajax({ url: "'.$_SESSION[$guid]['absoluteURL'].'/modules/Rubrics/rubrics_data_saveAjax.php", type: "GET", data: {mode: "Add", gibbonRubricID : "'.$gibbonRubricID.'", gibbonPersonID : "'.$gibbonPersonID.'",gibbonRubricCellID : "'.$cells[$rows[$i]['gibbonRubricRowID']][$columns[$n]['gibbonRubricColumnID']]['gibbonRubricCellID'].'",contextDBTable : "'.$contextDBTable.'",contextDBTableID : "'.$contextDBTableID.'"}, dataType: "html"});';
						$output .= '}';
						$output .= 'else {';
						$output .= '$("#'.$rows[$i]['gibbonRubricRowID'].'-'.$columns[$n]['gibbonRubricColumnID']."\").css('background', 'none').css('background-color', '#fbfbfb');";
						$output .= 'var request=$.ajax({ url: "'.$_SESSION[$guid]['absoluteURL'].'/modules/Rubrics/rubrics_data_saveAjax.php", type: "GET", data: {mode: "Remove", gibbonRubricID : "'.$gibbonRubricID.'", gibbonPersonID : "'.$gibbonPersonID.'",gibbonRubricCellID : "'.$cells[$rows[$i]['gibbonRubricRowID']][$columns[$n]['gibbonRubricColumnID']]['gibbonRubricCellID'].'",contextDBTable : "'.$contextDBTable.'",contextDBTableID : "'.$contextDBTableID.'"}, dataType: "html"});';
						$output .= '}';
						$output .= '});';
						$output .= '});';
						$output .= '</script>';
					}

					try {
						$dataEntry = array('gibbonRubricCellID' => @$cells[$rows[$i]['gibbonRubricRowID']][$columns[$n]['gibbonRubricColumnID']]['gibbonRubricCellID'], 'gibbonPersonID' => $gibbonPersonID, 'contextDBTable' => $contextDBTable, 'contextDBTableID' => $contextDBTableID);
						$sqlEntry = 'SELECT * FROM gibbonRubricEntry WHERE gibbonRubricCellID=:gibbonRubricCellID AND gibbonPersonID=:gibbonPersonID AND contextDBTable=:contextDBTable AND contextDBTableID=:contextDBTableID';
						$resultEntry = $connection2->prepare($sqlEntry);
						$resultEntry->execute($dataEntry);
					} catch (PDOException $e) {
						echo "<div class='error'>".$e->getMessage().'</div>';
					}

					$bgcolor = '#fbfbfb';
					if ($resultEntry->rowCount() == 1) {
						$bgcolor = '#79FA74';
					}
					$output .= "<td id='".$rows[$i]['gibbonRubricRowID'].'-'.$columns[$n]['gibbonRubricColumnID']."' style='background: none; background-color: $bgcolor; height: 100%; vertical-align: top'>";
					$output .= "<div class='currentView' style='font-size: 90%'>".@$cells[$rows[$i]['gibbonRubricRowID']][$columns[$n]['gibbonRubricColumnID']]['contents'].'</div>';
					$output .= "<div class='historical' style='font-size: 90%'>";

					if (isset($cells[$rows[$i]['gibbonRubricRowID']][$columns[$n]['gibbonRubricColumnID']]['context'])) {
						$arrayHistorical = explode('<br/>', $cells[$rows[$i]['gibbonRubricRowID']][$columns[$n]['gibbonRubricColumnID']]['context']);
						$countHistorical = count($arrayHistorical) - 1;
					} else {
						$arrayHistorical = array();
						$countHistorical = 0;
					}
					$countHistorical = count($arrayHistorical) - 1;
					if ($countHistorical > 0) {
						$output .= '<b><u>'.__($guid, 'Total Occurences:').' '.$countHistorical.'</u></b><br/>';
						for ($h = 0; $h < $countHistorical; ++$h) {
							if ($h < 7) {
								$output .= ($h + 1).') '.$arrayHistorical[$h].'<br/>';
							}
						}
						if ($countHistorical > 7) {
							$output .= '<b>'.__($guid, 'Older occurrences not shown...').'</b>';
						}
					}
					$output .= '</div>';
					$output .= "<input type='hidden' name='gibbonRubricColumnID[]' value='".$columns[$n]['gibbonRubricColumnID']."'>";
					$output .= "<input type='hidden' name='gibbonRubricRowID[]' value='".$rows[$i]['gibbonRubricRowID']."'>";
					$output .= "<input type='hidden' name='gibbonRubricCellID[]' value='".@$cells[$rows[$i]['gibbonRubricRowID']][$columns[$n]['gibbonRubricColumnID']]['gibbonRubricCellID']."'>";
					$output .= '</td>';
				}
				$output .= '</tr>';
			}
            $output .= '</table>';
            $output .= '</form>';
        }
    }

    return $output;
}
