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

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

//Search & Filters
$search = null;
if (isset($_GET['search'])) {
    $search = $_GET['search'];
}
$filter2 = null;
if (isset($_GET['filter2'])) {
    $filter2 = $_GET['filter2'];
}

if (isActionAccessible($guid, $connection2, '/modules/Rubrics/rubrics_duplicate.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __('The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        if ($highestAction != 'Manage Rubrics_viewEditAll' and $highestAction != 'Manage Rubrics_viewAllEditLearningArea') {
            echo "<div class='error'>";
            echo __('You do not have access to this action.');
            echo '</div>';
        } else {
            //Proceed!
            $page->breadcrumbs
                ->add(__('Manage Rubrics'), 'rubrics.php', ['search' => $search, 'filter2' => $filter2])
                ->add(__('Duplicate Rubric'));

            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            //Check if school year specified
            $gibbonRubricID = $_GET['gibbonRubricID'];
            if ($gibbonRubricID == '') {
                echo "<div class='error'>";
                echo __('You have not specified one or more required parameters.');
                echo '</div>';
            } else {
                
                    $data = array('gibbonRubricID' => $gibbonRubricID);
                    $sql = 'SELECT * FROM gibbonRubric WHERE gibbonRubricID=:gibbonRubricID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);

                if ($result->rowCount() != 1) {
                    echo "<div class='error'>";
                    echo __('The specified record does not exist.');
                    echo '</div>';
                } else {
                    //Let's go!
                    $values = $result->fetch();

                    if ($search != '' or $filter2 != '') {
                        echo "<div class='linkTop'>";
                        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Rubrics/rubrics.php&search=$search&filter2=$filter2'>".__('Back to Search Results').'</a>';
                        echo '</div>';
					}
					
					$scopes = array(
						'School' => __('School'),
						'Learning Area' => __('Learning Area'),
					);

					$form = Form::create('addRubric', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/rubrics_duplicateProcess.php?gibbonRubricID='.$gibbonRubricID.'&search='.$search.'&filter2='.$filter2);

					$form->addHiddenValue('address', $_SESSION[$guid]['address']);
					
					$form->addRow()->addHeading(__('Rubric Basics'));

					$row = $form->addRow();
                        $row->addLabel('scope', 'Scope');
                        
					if ($highestAction == 'Manage Rubrics_viewEditAll') {
                        $row->addSelect('scope')->fromArray($scopes)->required()->placeholder();
                        $form->toggleVisibilityByClass('learningAreaRow')->onSelect('scope')->when('Learning Area');
					} else if ($highestAction == 'Manage Rubrics_viewAllEditLearningArea') {
						$row->addTextField('scope')->readOnly()->setValue('Learning Area');
					}

					if ($highestAction == 'Manage Rubrics_viewEditAll') {
						$data = array();
						$sql = "SELECT gibbonDepartmentID as value, name FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name";
					} else if ($highestAction == 'Manage Rubrics_viewAllEditLearningArea') {
						$data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
						$sql = "SELECT gibbonDepartment.gibbonDepartmentID as value, gibbonDepartment.name FROM gibbonDepartment JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Teacher (Curriculum)') AND type='Learning Area' ORDER BY name";
					}
					
					$row = $form->addRow()->addClass('learningAreaRow');
						$row->addLabel('gibbonDepartmentID', __('Learning Area'));
						$row->addSelect('gibbonDepartmentID')->fromQuery($pdo, $sql, $data)->required()->placeholder();

					$row = $form->addRow();
						$row->addLabel('name', __('Name'));
						$row->addTextField('name')->maxLength(50)->required();
						
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

