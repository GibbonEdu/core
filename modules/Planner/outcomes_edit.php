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

use Gibbon\Http\Url;
use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs
    ->add(__('Manage Outcomes'), 'outcomes.php')
    ->add(__('Edit Outcome'));

if (isActionAccessible($guid, $connection2, '/modules/Planner/outcomes_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        if ($highestAction != 'Manage Outcomes_viewEditAll' and $highestAction != 'Manage Outcomes_viewAllEditLearningArea') {
            $page->addError(__('You do not have access to this action.'));
        } else {
            //Proceed!
            $filter2 = '';
            if (isset($_GET['filter2'])) {
                $filter2 = $_GET['filter2'] ?? '';
            }

            if ($filter2 != '') {
                $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Planner', 'outcomes.php')->withQueryParam('filter2', $filter2));
            }

            //Check if gibbonOutcomeID specified
            $gibbonOutcomeID = $_GET['gibbonOutcomeID'] ?? '';
            if ($gibbonOutcomeID == '') {
                $page->addError(__('You have not specified one or more required parameters.'));
            } else {
                try {
                    if ($highestAction == 'Manage Outcomes_viewEditAll') {
                        $data = array('gibbonOutcomeID' => $gibbonOutcomeID);
                        $sql = 'SELECT * FROM gibbonOutcome WHERE gibbonOutcomeID=:gibbonOutcomeID';
                    } elseif ($highestAction == 'Manage Outcomes_viewAllEditLearningArea') {
                        $data = array('gibbonOutcomeID' => $gibbonOutcomeID, 'gibbonPersonID' => $session->get('gibbonPersonID'));
                        $sql = "SELECT gibbonOutcome.* FROM gibbonOutcome JOIN gibbonDepartment ON (gibbonOutcome.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) AND NOT gibbonOutcome.gibbonDepartmentID IS NULL WHERE gibbonOutcomeID=:gibbonOutcomeID AND (role='Coordinator' OR role='Teacher (Curriculum)') AND gibbonPersonID=:gibbonPersonID AND scope='Learning Area'";
                    }
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                }

                if ($result->rowCount() != 1) {
                    $page->addError(__('The specified record does not exist.'));
                } else {
                    //Let's go!
					$values = $result->fetch(); 
					
					$form = Form::create('outcomes', $session->get('absoluteURL').'/modules/'.$session->get('module').'/outcomes_editProcess.php?gibbonOutcomeID='.$gibbonOutcomeID.'&filter2='.$filter2);
					$form->setFactory(DatabaseFormFactory::create($pdo));
					
					$form->addHiddenValue('address', $session->get('address'));

					$row = $form->addRow();
                        $row->addLabel('scope', 'Scope');
                        $row->addTextField('scope')->required()->readOnly();

                    if ($values['scope'] == 'Learning Area') {
                        $sql = "SELECT name FROM gibbonDepartment WHERE gibbonDepartmentID=:gibbonDepartmentID";
                        $result = $pdo->executeQuery(array('gibbonDepartmentID' => $values['gibbonDepartmentID']), $sql);
                        $learningArea = ($result->rowCount() > 0)? $result->fetchColumn(0) : $values['gibbonDepartmentID'];

                        $form->addHiddenValue('gibbonDepartmentID', $values['gibbonDepartmentID']);
                        $row = $form->addRow();
                            $row->addLabel('departmentName', __('Learning Area'));
                            $row->addTextField('departmentName')->required()->readOnly()->setValue($learningArea);
					}
					
					$row = $form->addRow();
						$row->addLabel('name', __('Name'));
						$row->addTextField('name')->required()->maxLength(100);

					$row = $form->addRow();
						$row->addLabel('nameShort', __('Short Name'));
						$row->addTextField('nameShort')->required()->maxLength(14);

					$row = $form->addRow();
						$row->addLabel('active', __('Active'));
						$row->addYesNo('active')->required();

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
