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
    ->add(__('Add Outcome'));

if (isActionAccessible($guid, $connection2, '/modules/Planner/outcomes_add.php') == false) {
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
            $editLink = '';
            if (isset($_GET['editID'])) {
                $editLink = $session->get('absoluteURL').'/index.php?q=/modules/Planner/outcomes_edit.php&gibbonOutcomeID='.$_GET['editID'].'&filter2='.$_GET['filter2'];
            }
            $page->return->setEditLink($editLink);


            $filter2 = '';
            if (isset($_GET['filter2'])) {
                $filter2 = $_GET['filter2'] ?? '';
            }

            if ($filter2 != '') {
                $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Planner', 'outcomes.php')->withQueryParam('filter2', $filter2));
			}

			$scopes = array(
                'School' => __('School'),
                'Learning Area' => __('Learning Area'),
            );

			$form = Form::create('outcomes', $session->get('absoluteURL').'/modules/'.$session->get('module').'/outcomes_addProcess.php?filter2='.$filter2);
			$form->setFactory(DatabaseFormFactory::create($pdo));

			$form->addHiddenValue('address', $session->get('address'));

			$row = $form->addRow();
                $row->addLabel('scope', __('Scope'));
            if ($highestAction == 'Manage Outcomes_viewEditAll') {
                $row->addSelect('scope')->fromArray($scopes)->required()->placeholder();
            } elseif ($highestAction == 'Manage Outcomes_viewAllEditLearningArea') {
                $row->addSelect('scope')->fromArray($scopes)->required()->readonly()->selected('Learning Area');
			}

			if ($highestAction == 'Manage Outcomes_viewEditAll') {
				$data = array();
				$sql = "SELECT gibbonDepartmentID as value, name FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name";
			} elseif ($highestAction == 'Manage Outcomes_viewAllEditLearningArea') {
				$data = array('gibbonPersonID' => $session->get('gibbonPersonID'));
				$sql = "SELECT gibbonDepartment.gibbonDepartmentID as value, gibbonDepartment.name FROM gibbonDepartment JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Teacher (Curriculum)') AND type='Learning Area' ORDER BY name";
			}


            if ($highestAction == 'Manage Outcomes_viewEditAll') {
                $form->toggleVisibilityByClass('learningAreaRow')->onSelect('scope')->when('Learning Area');
            }
            $row = $form->addRow()->addClass('learningAreaRow');
                $row->addLabel('gibbonDepartmentID', __('Learning Area'));
                $row->addSelect('gibbonDepartmentID')->fromQuery($pdo, $sql, $data)->required()->placeholder();

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
				$row->addCheckboxYearGroup('gibbonYearGroupIDList')->addCheckAllNone();

			$row = $form->addRow();
				$row->addSubmit();

			echo $form->getOutput();
        }
    }
}
