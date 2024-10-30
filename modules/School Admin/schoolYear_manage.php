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

use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\School\SchoolYearGateway;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/schoolYear_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Manage School Years'));

    $schoolYearGateway = $container->get(SchoolYearGateway::class);

    // QUERY
    $criteria = $schoolYearGateway->newQueryCriteria(true)
        ->sortBy(['sequenceNumber'])
        ->fromPOST();

    $schoolYears = $schoolYearGateway->querySchoolYears($criteria);

    // DATA TABLE
    $table = DataTable::createPaginated('schoolYearManage', $criteria);

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/School Admin/schoolYear_manage_add.php')
        ->displayLabel();

    $table->modifyRows(function($schoolYear, $row) {
        if ($schoolYear['status'] == 'Current') $row->addClass('current');
        return $row;
    });

    $table->addColumn('sequenceNumber', __('Sequence'))->width('10%');
    $table->addColumn('name', __('Name'));
    $table->addColumn('dates', __('Dates'))
          ->format(Format::using('dateRange', ['firstDay', 'lastDay']))
          ->sortable(['firstDay', 'lastDay']);
    $table->addColumn('status', __('Status'))->translatable();
        
    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonSchoolYearID')
        ->format(function ($schoolYear, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/School Admin/schoolYear_manage_edit.php');

            if ($schoolYear['status'] != 'Current') {
                $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/School Admin/schoolYear_manage_delete.php');
            }
        });

    echo $table->render($schoolYears);
}
