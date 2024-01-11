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
use Gibbon\Domain\School\YearGroupGateway;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/yearGroup_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Manage Year Groups'));

    $yearGroupGateway = $container->get(YearGroupGateway::class);

    // QUERY
    $criteria = $yearGroupGateway->newQueryCriteria()
        ->sortBy(['sequenceNumber'])
        ->fromPOST();

    $yearGroups = $yearGroupGateway->queryYearGroups($criteria);

    // DATA TABLE
    $table = DataTable::createPaginated('yearGroupManage', $criteria);

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/School Admin/yearGroup_manage_add.php')
        ->displayLabel();

    $table->addDraggableColumn('gibbonYearGroupID', $session->get('absoluteURL').'/modules/School Admin/yearGroup_manage_editOrderAjax.php');

    $table->addColumn('name', __('Name'));
    $table->addColumn('nameShort', __('Short Name'));
    $table->addColumn('gibbonPersonIDHOY', __('Head of Year'))
        ->format(function($values) {
            if (!empty($values['preferredName']) && !empty($values['surname'])) {
                return Format::name('', $values['preferredName'], $values['surname'], 'Staff', false, true);
            }
        });
        
    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonYearGroupID')
        ->format(function ($facilities, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/School Admin/yearGroup_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/School Admin/yearGroup_manage_delete.php');
        });

    echo $table->render($yearGroups);

}
