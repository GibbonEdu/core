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

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Forms\Prefab\BulkActionForm;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\FreeLearning\Domain\UnitGateway;
use Gibbon\Module\FreeLearning\Forms\FreeLearningFormFactory;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Get action with highest precedence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        $page->breadcrumbs->add(__m('Manage Units'));

        $gibbonDepartmentID = $_REQUEST['gibbonDepartmentID'] ?? '';
        $difficulty = $_GET['difficulty'] ?? '';
        $name = $_GET['name'] ?? '';
        $gibbonYearGroupIDMinimum = $_GET['gibbonYearGroupIDMinimum'] ?? '';

        // QUERY
        $unitGateway = $container->get(UnitGateway::class);
        $criteria = $unitGateway->newQueryCriteria(true)
            ->searchBy($unitGateway->getSearchableColumns(), $name)
            ->sortBy('name')
            ->filterBy('department', $gibbonDepartmentID)
            ->filterBy('difficulty', $difficulty)
            ->filterBy('gibbonYearGroupIDMinimum', $gibbonYearGroupIDMinimum)
            ->fromPOST();

        // FORM
        $form = Form::create('filter', $session->get('absoluteURL').'/index.php', 'get');
        $form->setFactory(FreeLearningFormFactory::create($pdo));
        $form->setTitle(__('Filter'));

        $form->setClass('noIntBorder w-full');
        $form->addHiddenValue('q', '/modules/Free Learning/units_manage.php');

        $learningAreas = $unitGateway->selectLearningAreasAndCourses($highestAction != 'Manage Units_all' ? $session->get('gibbonPersonID') : null, 'N', 'Staff', null, 'Browse Units_all', 'Manage');
        $row = $form->addRow();
            $row->addLabel('gibbonDepartmentID', __('Learning Area & Course'));
            $row->addSelect('gibbonDepartmentID')->fromResults($learningAreas, 'groupBy')->selected($gibbonDepartmentID)->placeholder();

        $difficulties = array_map('trim', explode(',', $container->get(SettingGateway::class)->getSettingByScope('Free Learning', 'difficultyOptions')));
        $row = $form->addRow();
            $row->addLabel('difficulty', __('Difficulty'));
            $row->addSelect('difficulty')->fromArray($difficulties)->selected($difficulty)->placeholder();

        $row = $form->addRow();
            $row->addLabel('name', __m('Unit/Course Name'));
            $row->addTextField('name')->setValue($criteria->getSearchText());

        $row = $form->addRow();
            $row->addLabel('gibbonYearGroupIDMinimum', __m('Minimum Year Group'));
            $row->addSelectYearGroup('gibbonYearGroupIDMinimum')->placeholder()->selected($gibbonYearGroupIDMinimum);

        $row = $form->addRow();
            $row->addSearchSubmit($session, __('Clear Filters'));

        echo $form->getOutput();


        if ($highestAction == 'Manage Units_all') {
            $units = $unitGateway->queryAllUnits($criteria, $session->get('gibbonPersonID'));
        } else {
            $units = $unitGateway->queryUnitsByLearningAreaStaff($criteria, $session->get('gibbonPersonID'));
        }

        $form = BulkActionForm::create('bulkAction', $session->get('absoluteURL').'/modules/Free Learning/units_manageProcessBulk.php');

        $bulkActions = ['Export' => __('Export'), 'Duplicate' => __('Duplicate')];
        if ($highestAction == 'Manage Units_all') {
            $bulkActions += ['Lock' => __m('Lock'), 'Unlock' => __m('Unlock')];
        }
        $col = $form->createBulkActionColumn($bulkActions);
            $col->addSubmit(__('Go'));

        // DATA TABLE
        $table = $form->addRow()->addDataTable('units', $criteria)->withData($units);
        $table->setTitle(__('View'));

        $table->addHeaderAction('add', __('Add'))
            ->addParam('gibbonDepartmentID', $gibbonDepartmentID)
            ->addParam('difficulty', $difficulty)
            ->addParam('name', $name)
            ->addParam('gibbonYearGroupIDMinimum', $gibbonYearGroupIDMinimum)
            ->setURL('/modules/Free Learning/units_manage_add.php')
            ->displayLabel();

        $table->addHeaderAction('import', __('Import'))
            ->setURL('/modules/Free Learning/units_manage_import.php')
            ->displayLabel();

        $table->modifyRows(function ($unit, $row) {
            if ($unit['active'] != 'Y') $row->addClass('error');
            return $row;
        });

        $table->addMetaData('bulkActions', $col);

        $table->addMetaData('filterOptions', [
            'active:Y'        => __('Units').': '.__('Active'),
            'active:N'        => __('Units').': '.__('Inactive'),
            'access:students' => __m('Available To Students'),
            'access:staff'    => __m('Available To Staff'),
            'access:parents'  => __m('Available To Parents'),
            'access:other'    => __m('Available To Other'),
        ]);

        $table->addColumn('name', __m('Unit Name'))
            ->format(function ($unit) {
                $output = $unit["name"];
                $output .= $unit["editLock"] == "Y" ? Format::tag(__('Locked'), 'dull ml-2') : '';
                return $output;
            });

        $table->addColumn('difficulty', __m('Difficulty'));
        $table->addColumn('learningArea', __m('Learning Areas'))
            ->format(function ($unit) {
                return !empty($unit['learningArea'])
                    ? $unit['learningArea']
                    : Format::small(__('None'));
            });

        $table->addColumn('active', __('Active'))->format(Format::using('yesNo', 'active'));

        // ACTIONS
        $canBrowseUnits = isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse.php');
        $table->addActionColumn()
            ->addParam('gibbonDepartmentID', $gibbonDepartmentID)
            ->addParam('difficulty', $difficulty)
            ->addParam('name', $name)
            ->addParam('gibbonYearGroupIDMinimum', $gibbonYearGroupIDMinimum)
            ->addParam('freeLearningUnitID')
            ->format(function ($unit, $actions) use ($canBrowseUnits, $highestAction) {
                if ($canBrowseUnits) {
                    $actions->addAction('view', __('View'))
                        ->addParam('sidebar', 'true')
                        ->addParam('showInactive', 'Y')
                        ->setURL('/modules/Free Learning/units_browse_details.php');
                }

                if ($highestAction == "Manage Units_all" || $unit['editLock'] == "N") {
                    $actions->addAction('edit', __('Edit'))
                            ->setURL('/modules/Free Learning/units_manage_edit.php');
                }

                $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/Free Learning/units_manage_delete.php');
            });

        $table->addCheckboxColumn('freeLearningUnitID');

        echo $form->getOutput();
    }
}
