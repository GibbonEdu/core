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

if (isActionAccessible($guid, $connection2, '/modules/Planner/outcomes_edit.php') == false) {
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
        if ($highestAction != 'Manage Outcomes_viewEditAll' and $highestAction != 'Manage Outcomes_viewAllEditLearningArea') {
            echo "<div class='error'>";
            echo __($guid, 'You do not have access to this action.');
            echo '</div>';
        } else {
            //Proceed!
            echo "<div class='trail'>";
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/outcomes.php'>".__($guid, 'Manage Outcomes')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Outcome').'</div>';
            echo '</div>';

            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            $filter2 = '';
            if (isset($_GET['filter2'])) {
                $filter2 = $_GET['filter2'];
            }

            if ($filter2 != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Planner/outcomes.php&filter2='.$filter2."'>".__($guid, 'Back to Search Results').'</a>';
                echo '</div>';
            }

            //Check if school year specified
            $gibbonOutcomeID = $_GET['gibbonOutcomeID'];
            if ($gibbonOutcomeID == '') {
                echo "<div class='error'>";
                echo __($guid, 'You have not specified one or more required parameters.');
                echo '</div>';
            } else {
                try {
                    if ($highestAction == 'Manage Outcomes_viewEditAll') {
                        $data = array('gibbonOutcomeID' => $gibbonOutcomeID);
                        $sql = 'SELECT * FROM gibbonOutcome WHERE gibbonOutcomeID=:gibbonOutcomeID';
                    } elseif ($highestAction == 'Manage Outcomes_viewAllEditLearningArea') {
                        $data = array('gibbonOutcomeID' => $gibbonOutcomeID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                        $sql = "SELECT gibbonOutcome.* FROM gibbonOutcome JOIN gibbonDepartment ON (gibbonOutcome.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) AND NOT gibbonOutcome.gibbonDepartmentID IS NULL WHERE gibbonOutcomeID=:gibbonOutcomeID AND (role='Coordinator' OR role='Teacher (Curriculum)') AND gibbonPersonID=:gibbonPersonID AND scope='Learning Area'";
                    }
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
					
					$form = Form::create('outcomes', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/outcomes_editProcess.php?gibbonOutcomeID='.$gibbonOutcomeID.'&filter2='.$filter2);
					$form->setFactory(DatabaseFormFactory::create($pdo));
					
					$form->addHiddenValue('address', $_SESSION[$guid]['address']);

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
						$row->addTextField('name')->isRequired()->maxLength(100);

					$row = $form->addRow();
						$row->addLabel('nameShort', __('Short Name'));
						$row->addTextField('nameShort')->isRequired()->maxLength(14);

					$row = $form->addRow();
						$row->addLabel('active', __('Active'));
						$row->addYesNo('active')->isRequired();

					$sql = "SELECT DISTINCT category FROM gibbonOutcome ORDER BY category";
					$result = $pdo->executeQuery(array(), $sql);
					$categories = ($result->rowCount() > 0)? $result->fetchAll(\PDO::FETCH_COLUMN, 0) : array();

					$row = $form->addRow();
						$row->addLabel('category', __('Category'));
						$row->addTextField('category')->maxLength(100)->autocomplete($categories);
						
					$row = $form->addRow();
						$row->addLabel('description', __('Description'));
						$row->addTextArea('description')->setRows(5);

					$row = $form->addRow();
						$row->addLabel('gibbonYearGroupIDList', __('Year Groups'))->description(__('Relevant student year groups'));
						$row->addCheckboxYearGroup('gibbonYearGroupIDList')->addCheckAllNone()->loadFromCSV($values);

					$row = $form->addRow();
						$row->addSubmit();

					$form->loadAllValuesFrom($values);

					echo $form->getOutput();

                }
            }
        }
    }
}