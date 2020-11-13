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
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\Rubrics\RubricGateway;
use Gibbon\Domain\Departments\DepartmentGateway;

if (isActionAccessible($guid, $connection2, '/modules/Rubrics/rubrics.php') == false) {
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
        //Proceed!
        $page->breadcrumbs->add(__('Manage Rubrics'));

        // Register scripts available to the core, but not included by default
        $page->scripts->add('chart');
    
        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        $search = isset($_REQUEST['search'])? $_REQUEST['search'] : '';
        $department = isset($_POST['filter2'])? $_POST['filter2'] : '';
        $yearGroups = getYearGroups($connection2);

        $rubricGateway = $container->get(RubricGateway::class);
        $departmentGateway = $container->get(DepartmentGateway::class);

        // QUERY
        $criteria = $rubricGateway->newQueryCriteria(true)
            ->searchBy($rubricGateway->getSearchableColumns(), $search)
            ->sortBy(['scope', 'category', 'name'])
            ->filterBy('department', $department)
            ->fromPOST();

        // SEARCH
        $form = Form::create('searchForm', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/rubrics.php');
        $form->setTitle(__('Filter'));
        $form->setClass('noIntBorder fullWidth');

        $row = $form->addRow();
            $row->addLabel('search', __('Search For'))->description(__('Rubric name.'));
            $row->addTextField('search')->setValue($criteria->getSearchText());

        $sql = "SELECT gibbonDepartmentID as value, name FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name";
        $row = $form->addRow();
            $row->addLabel('filter2', __('Learning Areas'));
            $row->addSelect('filter2')
                ->fromArray(array('' => __('All Learning Areas')))
                ->fromQuery($pdo, $sql)
                ->selected($department);

        $row = $form->addRow();
            $row->addSearchSubmit($gibbon->session, __('Clear Filters'));

        echo $form->getOutput();

        $rubrics = $rubricGateway->queryRubrics($criteria);

        // DATA TABLE
        $table = DataTable::createPaginated('rubrics', $criteria);
        $table->setTitle(__('Rubrics'));

        $table->modifyRows(function($query, $row) {
            if ($query['active'] != 'Y') $row->addClass('error');
            return $row;
        });

        if ($highestAction == 'Manage Rubrics_viewEditAll' or $highestAction == 'Manage Rubrics_viewAllEditLearningArea') {
            $table->addHeaderAction('add', __('Add'))
                ->setURL('/modules/Rubrics/rubrics_add.php')
                ->addParam('search', $search)
                ->displayLabel();
        }

        // COLUMNS
        $table->addExpandableColumn('description');
        $table->addColumn('scope', __('Scope'))
            ->context('primary')
            ->width('15%')
            ->format(function($rubric) {
                if ($rubric['scope'] == 'School') {
                    return '<strong>'.__('School').'</strong>';
                } else {
                    return '<strong>'.__('Learning Area').'</strong><br/>'.Format::small($rubric['learningArea']);
                }
            });
        $table->addColumn('category', __('Category'))->width('15%');
        $table->addColumn('name', __('Name'))
            ->context('primary')
            ->width('35%');
        $table->addColumn('yearGroups', __('Year Groups'))
            ->format(function($activity) use ($yearGroups) {
                return ($activity['yearGroupCount'] >= count($yearGroups)/2)? '<i>'.__('All').'</i>' : $activity['yearGroups'];
            });
        $table->addColumn('active', __('Active'))->format(Format::using('yesNo', 'active'));

        // ACTIONS
        $table->addActionColumn()
            ->addParam('gibbonRubricID')
            ->addParam('search', $criteria->getSearchText(true))
            ->format(function ($rubric, $actions) use ($guid, $highestAction, $departmentGateway) {
                $canEdit = false;
                if ($highestAction == 'Manage Rubrics_viewEditAll') {
                    $canEdit = true;
                } else if ($highestAction == 'Manage Rubrics_viewAllEditLearningArea' && $rubric['scope'] == 'Learning Area') {
                    $departmentMember = $departmentGateway->selectMemberOfDepartmentByRole($rubric['gibbonDepartmentID'], $_SESSION[$guid]['gibbonPersonID'], ['Coordinator', 'Teacher (Curriculum)']);
                    $canEdit = $departmentMember->rowCount() > 0;
                }

                if ($canEdit) {
                    $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/Rubrics/rubrics_edit.php')
                        ->addParam('sidebar', 'false');

                    $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/Rubrics/rubrics_delete.php');

                    $actions->addAction('duplicate', __('Duplicate'))
                        ->setURL('/modules/Rubrics/rubrics_duplicate.php')
                        ->setIcon('copy');
                }

                if ($rubric['active'] == 'Y') {
                    $actions->addAction('view', __('View'))
                        ->setURL('/modules/Rubrics/rubrics_view_full.php')
                        ->modalWindow(1100, 550);
                    }
            });

        echo $table->render($rubrics);
    }
}
