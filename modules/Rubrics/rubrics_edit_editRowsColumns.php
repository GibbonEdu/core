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

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

//Search & Filters
$search = null;
if (isset($_GET['search'])) {
    $search = $_GET['search'];
}
$filter2 = null;
if (isset($_GET['filter2'])) {
    $filter2 = $_GET['filter2'];
}

if (isActionAccessible($guid, $connection2, '/modules/Rubrics/rubrics_edit_editRowsColumns.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        if ($highestAction != 'Manage Rubrics_viewEditAll' and $highestAction != 'Manage Rubrics_viewAllEditLearningArea') {
            echo "<div class='error'>";
            echo __($guid, 'You do not have access to this action.');
            echo '</div>';
        } else {
            //Proceed!
            echo "<div class='trail'>";
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/rubrics.php&search=$search&filter2=$filter2'>".__($guid, 'Manage Rubrics')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/rubrics_edit.php&gibbonRubricID='.$_GET['gibbonRubricID']."&search=$search&filter2=$filter2'>".__($guid, 'Edit Rubric')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Rubric Rows & Columns').'</div>';
            echo '</div>';

            if ($search != '' or $filter2 != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Rubrics/rubrics_edit.php&gibbonRubricID='.$_GET['gibbonRubricID']."&search=$search&filter2=$filter2&sidebar=false'>".__($guid, 'Back').'</a>';
                echo '</div>';
            }

            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            //Check if school year specified
            $gibbonRubricID = $_GET['gibbonRubricID'];
            if ($gibbonRubricID == '') {
                echo "<div class='error'>";
                echo __($guid, 'You have not specified one or more required parameters.');
                echo '</div>';
            } else {
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
                    echo __($guid, 'The specified record does not exist.');
                    echo '</div>';
                } else {
                    //Let's go!
					$values = $result->fetch(); 
					
					$form = Form::create('addRubric', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/rubrics_edit_editRowsColumnsProcess.php?gibbonRubricID='.$gibbonRubricID.'&search='.$search.'&filter2='.$filter2);

                    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
                    
                    $form->addRow()->addHeading(__('Rubric Basics'));

                    $row = $form->addRow();
                        $row->addLabel('scope', 'Scope');
                        $row->addTextField('scope')->isRequired()->readOnly();

                    if ($values['scope'] == 'Learning Area') {
                        $sql = "SELECT name FROM gibbonDepartment WHERE gibbonDepartmentID=:gibbonDepartmentID";
                        $result = $pdo->executeQuery(array('gibbonDepartmentID' => $values['gibbonDepartmentID']), $sql);
                        $learningArea = ($result->rowCount() > 0)? $result->fetchColumn(0) : $values['gibbonDepartmentID'];

                        $form->addHiddenValue('gibbonDepartmentID', $values['gibbonDepartmentID']);
                        $row = $form->addRow();
                            $row->addLabel('departmentName', __('Learning Area'));
                            $row->addTextField('departmentName')->isRequired()->readOnly()->setValue($learningArea);
					}

					$row = $form->addRow();
                        $row->addLabel('name', __('Name'));
						$row->addTextField('name')->maxLength(50)->isRequired()->readOnly();
						
					$form->addRow()->addHeading(__('Rows'));

					// Get outcomes by year group
					$data = array('gibbonYearGroupIDList' => $values['gibbonYearGroupIDList']);
					$sql = "SELECT gibbonOutcome.gibbonOutcomeID, gibbonOutcome.scope, gibbonOutcome.category, gibbonOutcome.name 
							FROM gibbonOutcome 
							LEFT JOIN gibbonYearGroup ON (FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonOutcome.gibbonYearGroupIDList))
							WHERE gibbonOutcome.active='Y' 
							AND FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, :gibbonYearGroupIDList)
							GROUP BY gibbonOutcome.gibbonOutcomeID
							ORDER BY gibbonOutcome.category, gibbonOutcome.name";
					$result = $pdo->executeQuery($data, $sql);
					
					// Build a set of outcomes grouped by scope
					$outcomes = ($result->rowCount() > 0)? $result->fetchAll() : array();
					$outcomes = array_reduce($outcomes, function($group, $item) {
						$name = !empty($item['category'])? $item['category'].' - '.$item['name'] : $item['name'];
 						$group[$item['scope'].' '.__('Outcomes')][$item['gibbonOutcomeID']] = $name;
						return $group;
					}, array());

					$typeOptions = array('Standalone' => __('Standalone'), 'Outcome Based' => __('Outcome Based'));
					
					$data = array('gibbonRubricID' => $gibbonRubricID);
					$sql = "SELECT gibbonRubricRowID, title, gibbonOutcomeID FROM gibbonRubricRow WHERE gibbonRubricID=:gibbonRubricID ORDER BY sequenceNumber";
                    $result = $pdo->executeQuery($data, $sql);
					
					if ($result->rowCount() <= 0) {
						$form->addRow()->addAlert(__('There are no records to display.'), 'error');
					} else {
						$count = 0;
						while ($rubricRow = $result->fetch()) {
							$type = ($rubricRow['gibbonOutcomeID'] != '')? 'Outcome Based' : 'Standalone';

							$row = $form->addRow();
								$row->addLabel('rowName'.$count, sprintf(__('Row %1$s Title'), ($count + 1)) );
								$column = $row->addColumn()->addClass('right');
								$column->addRadio('type'.$count)->fromArray($typeOptions)->inline()->checked($type);
								$column->addTextField('rowTitle['.$count.']')
									->setID('rowTitle'.$count)
									->addClass('rowTitle'.$count)
									->maxLength(40)
									->isRequired()
									->setValue($rubricRow['title']);
								$column->addSelect('gibbonOutcomeID['.$count.']')
									->setID('gibbonOutcomeID'.$count)
									->addClass('gibbonOutcomeID'.$count)
									->fromArray($outcomes)
									->isRequired()
									->placeholder()
									->selected($rubricRow['gibbonOutcomeID']);

							$form->toggleVisibilityByClass('rowTitle'.$count)->onRadio('type'.$count)->when('Standalone');
							$form->toggleVisibilityByClass('gibbonOutcomeID'.$count)->onRadio('type'.$count)->when('Outcome Based');
							$form->addHiddenValue('gibbonRubricRowID['.$count.']', $rubricRow['gibbonRubricRowID']);
								
							$count++;
						}
					}

					$form->addRow()->addHeading(__('Columns'));

					$data = array('gibbonRubricID' => $gibbonRubricID);
					$sql = "SELECT gibbonRubricColumnID, title, gibbonScaleGradeID FROM gibbonRubricColumn WHERE gibbonRubricID=:gibbonRubricID ORDER BY sequenceNumber";
                    $result = $pdo->executeQuery($data, $sql);
					
					if ($result->rowCount() <= 0) {
						$form->addRow()->addAlert(__('There are no records to display.'), 'error');
					} else {
						$count = 0;
						while ($rubricColumn = $result->fetch()) {
							$row = $form->addRow();
							$row->addLabel('columnName'.$count, sprintf(__('Column %1$s Title'), ($count + 1)));

							// Handle non-grade scale columns as a text field, otherwise a dropdown
							if ($values['gibbonScaleID'] == '') {
								$row->addTextField('columnTitle['.$count.']')
									->setID('columnTitle'.$count)
									->maxLength(20)
									->isRequired()
									->setValue($rubricColumn['title']);
							} else {
								$data = array('gibbonScaleID' => $values['gibbonScaleID']);
								$sql = "SELECT gibbonScaleGradeID as value, CONCAT(value, ' - ', descriptor) as name FROM gibbonScaleGrade WHERE gibbonScaleID=:gibbonScaleID AND NOT value='Incomplete' ORDER BY sequenceNumber";
								$row->addSelect('gibbonScaleGradeID['.$count.']')
									->setID('gibbonScaleGradeID'.$count)
									->fromQuery($pdo, $sql, $data)
									->isRequired()
									->selected($rubricColumn['gibbonScaleGradeID']);
							}
							$form->addHiddenValue('gibbonRubricColumnID['.$count.']', $rubricColumn['gibbonRubricColumnID']);

							$count++;
						}
					}

					$row = $form->addRow();
                        $row->addFooter();
                        $row->addSubmit();

                    $form->loadAllValuesFrom($values);
                    
					echo $form->getOutput();
                }
            }
        }
    }
}