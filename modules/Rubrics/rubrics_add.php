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

use Gibbon\Domain\Departments\DepartmentGateway;
use Gibbon\Domain\Rubrics\RubricGateway;
use Gibbon\Domain\School\GradeScaleGateway;
use Gibbon\Http\Url;
use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

//Search & Filters
$search = null;
if (isset($_GET['search'])) {
    $search = $_GET['search'] ?? '';
}
$filter2 = null;
if (isset($_GET['filter2'])) {
    $filter2 = $_GET['filter2'] ?? '';
}

if (isActionAccessible($guid, $connection2, '/modules/Rubrics/rubrics_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        if ($highestAction != 'Manage Rubrics_viewEditAll' and $highestAction != 'Manage Rubrics_viewAllEditLearningArea') {
            $page->addError(__('You do not have access to this action.'));
        } else {
            //Proceed!
            $page->breadcrumbs
                ->add(__('Manage Rubrics'), 'rubrics.php', ['search' => $search, 'filter2' => $filter2])
                ->add(__('Add Rubric'));

            if ($search != '' or $filter2 != '') {
                $params = [
                    "search" => $search,
                    "filter2" => $filter2,
                ];
                $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Rubrics', 'rubrics.php')->withQueryParams($params));
            }
            
            $scopes = array(
                'School' => __('School'),
                'Learning Area' => __('Learning Area'),
            );

            $form = Form::create('addRubric', $session->get('absoluteURL').'/modules/'.$session->get('module').'/rubrics_addProcess.php?search='.$search.'&filter2='.$filter2);
            $form->setFactory(DatabaseFormFactory::create($pdo));

            $form->addHiddenValue('address', $session->get('address'));
            
            $form->addRow()->addHeading('Rubric Basics', __('Rubric Basics'));

            $row = $form->addRow();
                $row->addLabel('scope', 'Scope');
            if ($highestAction == 'Manage Rubrics_viewEditAll') {
                $row->addSelect('scope')->fromArray($scopes)->required()->placeholder();
                $form->toggleVisibilityByClass('learningAreaRow')->onSelect('scope')->when('Learning Area');
            } else if ($highestAction == 'Manage Rubrics_viewAllEditLearningArea') {
                $form->addHiddenValue('scope', 'Learning Area');
                $row->addTextField('scopeText')->readOnly()->setValue(__('Learning Area'));
            }

            if ($highestAction == 'Manage Rubrics_viewEditAll') {

                $results = $container->get(DepartmentGateway::class)->selectDepartmentsOfTypeLearningArea();

            } else if ($highestAction == 'Manage Rubrics_viewAllEditLearningArea') {

                $results = $container->get(DepartmentGateway::class)->selectDepartmentsOfTypeLearningAreaByStaff($session->get('gibbonPersonID'));
            }

            $row = $form->addRow()->addClass('learningAreaRow');
                $row->addLabel('gibbonDepartmentID', __('Learning Area'));
                $row->addSelect('gibbonDepartmentID')->fromResults($results)->required()->placeholder();

            $row = $form->addRow();
                $row->addLabel('name', __('Name'));
                $row->addTextField('name')->maxLength(50)->required();

            $row = $form->addRow();
                $row->addLabel('active', __('Active'));
                $row->addYesNo('active')->required();

            $result = $container->get(RubricGateway::class)->selectDistinctRubricCategories();

            $categories = ($result->rowCount() > 0)? $result->fetchAll(\PDO::FETCH_COLUMN, 0) : array();

            $row = $form->addRow();
                $row->addLabel('category', __('Category'));
                $row->addTextField('category')->maxLength(100)->autocomplete($categories);

            $row = $form->addRow();
                $row->addLabel('description', __('Description'));
                $row->addTextArea('description')->setRows(5);

            $row = $form->addRow();
                $row->addLabel('gibbonYearGroupIDList[]', __('Year Groups'));
                $row->addCheckboxYearGroup('gibbonYearGroupIDList[]')->addCheckAllNone()->checkAll();

            $results = $container->get(GradeScaleGateway::class)->selectActiveGradeScales();

            $row = $form->addRow();
                $row->addLabel('gibbonScaleID', __('Grade Scale'))->description(__('Link columns to grades on a scale?'));
                $row->addSelect('gibbonScaleID')->fromResults($results)->placeholder();

            $form->addRow()->addHeading('Rubric Design', __('Rubric Design'));

            $row = $form->addRow();
                $row->addLabel('rows', __('Initial Rows'))->description(__('Rows store assessment strands.'));
                $row->addSelect('rows')->fromArray(range(1, 10))->required();

            $row = $form->addRow();
                $row->addLabel('columns', __('Initial Columns'))->description(__('Columns store assessment levels.'));
                $row->addSelect('columns')->fromArray(range(1, 10))->required();
            
            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();
            
            echo $form->getOutput();
        }
    }
}
