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

use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\System\SessionGateway;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/activeSessions.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Active Sessions'));

    $sessionGateway = $container->get(SessionGateway::class);
   
    // QUERY
    $criteria = $sessionGateway->newQueryCriteria()
        ->sortBy('timestampModified', 'DESC')
        ->pageSize(0)
        ->fromPOST();

    $sessions = $sessionGateway->queryActiveSessions($criteria);

    // DATA TABLE
    $table = DataTable::createPaginated('sessions', $criteria);
    $table->setTitle(__('View'));

    $table->modifyRows(function ($customField, $row) {
        // if ($customField['active'] == 'N') $row->addClass('error');
        return $row;
    });

    // $table->addHeaderAction('add', __('Add'))
    //     ->setURL('/modules/System Admin/customFields_add.php')
    //     ->addParam('context', $context != 'User' ? $context : '')
    //     ->displayLabel();

    $table->addColumn('name', __('Name'))
        ->context('primary')
        ->sortable(['surname', 'preferredName'])
        ->format(function ($values) {
            return !empty($values['gibbonPersonID'])
                ? Format::name($values['title'], $values['preferredName'], $values['surname'], 'Student', true, true)
                : __('Anonymous');
        });

    $table->addColumn('roleCategory', __('Role Category'))
        ->context('secondary');

    $table->addColumn('lastIPAddress', __('IP Address'))
        ->context('secondary');

    $table->addColumn('actionName', __('Page'));

    $table->addColumn('timestampCreated', __('Duration'))
        ->format(Format::using('relativeTime', ['timestampCreated', true, false]));

    $table->addColumn('timestampActive', __('Last Active'))
        ->format(Format::using('relativeTime', ['timestampModified', true, false]));

    $table->addColumn('timestampModified', __('Last Updated'))
        ->format(Format::using('dateTime', 'timestampModified'));
        
    echo $table->render($sessions);
}
