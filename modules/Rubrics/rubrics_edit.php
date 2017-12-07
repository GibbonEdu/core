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
use Gibbon\Forms\DatabaseFormFactory;

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

if (isActionAccessible($guid, $connection2, '/modules/Rubrics/rubrics_edit.php') == false) {
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
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/rubrics.php&search=$search&filter2=$filter2'>".__($guid, 'Manage Rubrics')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Rubric').'</div>';
            echo '</div>';

            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            if (isset($_GET['addReturn'])) {
                $addReturn = $_GET['addReturn'];
            } else {
                $addReturn = '';
            }
            $addReturnMessage = '';
            $class = 'error';
            if (!($addReturn == '')) {
                if ($addReturn == 'success0') {
                    $addReturnMessage = __($guid, 'Your request was completed successfully.');
                    $class = 'success';
                }
                echo "<div class='$class'>";
                echo $addReturnMessage;
                echo '</div>';
            }

            if (isset($_GET['columnDeleteReturn'])) {
                $columnDeleteReturn = $_GET['columnDeleteReturn'];
            } else {
                $columnDeleteReturn = '';
            }
            $columnDeleteReturnMessage = '';
            $class = 'error';
            if (!($columnDeleteReturn == '')) {
                if ($columnDeleteReturn == 'fail0') {
                    $columnDeleteReturnMessage = __($guid, 'Your request failed because you do not have access to this action.');
                } elseif ($columnDeleteReturn == 'fail1') {
                    $columnDeleteReturnMessage = __($guid, 'Your request failed because your inputs were invalid.');
                } elseif ($columnDeleteReturn == 'fail2') {
                    $columnDeleteReturnMessage = __($guid, 'Your request failed due to a database error.');
                } elseif ($columnDeleteReturn == 'fail3') {
                    $columnDeleteReturnMessage = __($guid, 'Your request failed because your inputs were invalid.');
                } elseif ($columnDeleteReturn == 'success0') {
                    $columnDeleteReturnMessage = __($guid, 'Your request was completed successfully.');
                    $class = 'success';
                }
                echo "<div class='$class'>";
                echo $columnDeleteReturnMessage;
                echo '</div>';
            }

            if (isset($_GET['rowDeleteReturn'])) {
                $rowDeleteReturn = $_GET['rowDeleteReturn'];
            } else {
                $rowDeleteReturn = '';
            }
            $rowDeleteReturnMessage = '';
            $class = 'error';
            if (!($rowDeleteReturn == '')) {
                if ($rowDeleteReturn == 'fail0') {
                    $rowDeleteReturnMessage = __($guid, 'Your request failed because you do not have access to this action.');
                } elseif ($rowDeleteReturn == 'fail1') {
                    $rowDeleteReturnMessage = __($guid, 'Your request failed because your inputs were invalid.');
                } elseif ($rowDeleteReturn == 'fail2') {
                    $rowDeleteReturnMessage = __($guid, 'Your request failed due to a database error.');
                } elseif ($rowDeleteReturn == 'fail3') {
                    $rowDeleteReturnMessage = __($guid, 'Your request failed because your inputs were invalid.');
                } elseif ($rowDeleteReturn == 'success0') {
                    $rowDeleteReturnMessage = __($guid, 'Your request was completed successfully.');
                    $class = 'success';
                }
                echo "<div class='$class'>";
                echo $rowDeleteReturnMessage;
                echo '</div>';
            }

            if (isset($_GET['cellEditReturn'])) {
                $cellEditReturn = $_GET['cellEditReturn'];
            } else {
                $cellEditReturn = '';
            }
            $cellEditReturnMessage = '';
            $class = 'error';
            if (!($cellEditReturn == '')) {
                if ($cellEditReturn == 'fail0') {
                    $cellEditReturnMessage = __($guid, 'Your request failed because you do not have access to this action.');
                } elseif ($cellEditReturn == 'fail1') {
                    $cellEditReturnMessage = __($guid, 'Your request failed because your inputs were invalid.');
                } elseif ($cellEditReturn == 'fail2') {
                    $cellEditReturnMessage = __($guid, 'Your request failed due to a database error.');
                } elseif ($cellEditReturn == 'fail3') {
                    $cellEditReturnMessage = __($guid, 'Your request failed because your inputs were invalid.');
                } elseif ($cellEditReturn == 'fail5') {
                    $cellEditReturnMessage = __($guid, 'Your request was successful, but some data was not properly saved.');
                } elseif ($cellEditReturn == 'success0') {
                    $cellEditReturnMessage = __($guid, 'Your request was completed successfully.');
                    $class = 'success';
                }
                echo "<div class='$class'>";
                echo $cellEditReturnMessage;
                echo '</div>';
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

                    if ($search != '' or $filter2 != '') {
                        echo "<div class='linkTop'>";
                        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Rubrics/rubrics.php&search=$search&filter2=$filter2'>".__($guid, 'Back to Search Results').'</a>';
                        echo '</div>';
                    }

                    $form = Form::create('addRubric', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/rubrics_editProcess.php?gibbonRubricID='.$gibbonRubricID.'&search='.$search.'&filter2='.$filter2);
                    $form->setFactory(DatabaseFormFactory::create($pdo));

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
                        $row->addTextField('name')->maxLength(50)->isRequired();

                    $row = $form->addRow();
                        $row->addLabel('active', __('Active'));
                        $row->addYesNo('active')->isRequired();

                    $sql = "SELECT DISTINCT category FROM gibbonRubric ORDER BY category";
                    $result = $pdo->executeQuery(array(), $sql);
                    $categories = ($result->rowCount() > 0)? $result->fetchAll(\PDO::FETCH_COLUMN, 0) : array();

                    $row = $form->addRow();
                        $row->addLabel('category', __('Category'));
                        $row->addTextField('category')->maxLength(100)->autocomplete($categories);

                    $row = $form->addRow();
                        $row->addLabel('description', __('Description'));
                        $row->addTextArea('description')->setRows(5);

                    $row = $form->addRow();
                        $row->addLabel('gibbonYearGroupIDList[]', __('Year Groups'));
                        $row->addCheckboxYearGroup('gibbonYearGroupIDList[]')->addCheckAllNone()->loadFromCSV($values);

                    $sql = "SELECT name FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID";
                    $result = $pdo->executeQuery(array('gibbonScaleID' => $values['gibbonScaleID']), $sql);
                    $gradeScaleName = ($result->rowCount() > 0)? $result->fetchColumn(0) : $values['gibbonScaleID'];

                    $form->addHiddenValue('gibbonScaleID', $values['gibbonScaleID']);
                    $row = $form->addRow();
                        $row->addLabel('gradeScale', __('Grade Scale'))->description(__('Link columns to grades on a scale?'));
                        $row->addTextField('gradeScale')->readOnly()->setValue($gradeScaleName);

                    $row = $form->addRow();
                        $row->addFooter();
                        $row->addSubmit();

                    $form->loadAllValuesFrom($values);
                    
                    echo $form->getOutput();

					echo '<a name="rubricDesign"></a>';
					echo '<table class="smallIntBorder" cellspacing="0" style="width:100%">';
						echo '<tr class="break">';
							echo '<td colspan=2>';
								echo '<h3>'. __($guid, 'Rubric Design') .'</h3>';
							echo '</td>';
						echo '</tr>';
					echo '</table>';

                    echo rubricEdit($guid, $connection2, $gibbonRubricID, $gradeScaleName, $search, $filter2);
                }
            }
        }
    }
}
