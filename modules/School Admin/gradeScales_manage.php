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

use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\School\GradeScaleGateway;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/gradeScales_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Manage Grade Scales'));
    echo '<p>';
    echo __('Grade scales are used through the Assess modules to control what grades can be entered into the system. Editing some of the inbuilt scales can impact other areas of the system: it is advised to take a backup of the entire system before doing this.');
    echo '</p>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gradeScaleGateway = $container->get(GradeScaleGateway::class);

    // QUERY
    $criteria = $gradeScaleGateway->newQueryCriteria(true)
        ->sortBy('name')
        ->fromPOST();

    $gradeScales = $gradeScaleGateway->queryGradeScales($criteria);

    // DATA TABLE
    $table = DataTable::createPaginated('gradeScaleManage', $criteria);

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/School Admin/gradeScales_manage_add.php')
        ->displayLabel();

    $table->modifyRows(function($gradeScale, $row) {
        if ($gradeScale['active'] != 'Y') $row->addClass('error');
        return $row;
    });

    $table->addColumn('name', __('Name'))
          ->description(__('Short Name'))
          ->format(function ($gradeScale) {
            return '<strong>' . __($gradeScale['name']) . '</strong><br/><small><i>' . __($gradeScale['nameShort']) . '</i></small>';
          });
    $table->addColumn('usage', __('Usage'))->translatable();
    $table->addColumn('active', __('Active'))->format(Format::using('yesNo', ['active']));
    $table->addColumn('numeric', __('Numeric'))->format(Format::using('yesNo', ['numeric']));
        
    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonScaleID')
        ->format(function ($gradeScale, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/School Admin/gradeScales_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/School Admin/gradeScales_manage_delete.php');
        });

    echo $table->render($gradeScales);
}
