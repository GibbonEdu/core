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
use Gibbon\Domain\Messenger\GroupGateway;

$page->breadcrumbs->add(__('Manage Groups'));

if (isActionAccessible($guid, $connection2, '/modules/Messenger/groups_manage.php') == false) {
    //Acess denied
    echo '<div class="error">';
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $groupGateway = $container->get(GroupGateway::class);

    $criteria = $groupGateway->newQueryCriteria()
        ->sortBy(['schoolYear', 'name'])
        ->fromPOST();

    $highestAction = getHighestGroupedAction($guid, '/modules/Messenger/groups_manage.php', $connection2);
    if ($highestAction == 'Manage Groups_all') {
        $groups = $groupGateway->queryGroups($criteria, $_SESSION[$guid]['gibbonSchoolYearID']);
    } else {
        $groups = $groupGateway->queryGroups($criteria, $_SESSION[$guid]['gibbonSchoolYearID'], $_SESSION[$guid]['gibbonPersonID']);
    }
    

    // DATA TABLE
    $table = DataTable::createPaginated('groupsManage', $criteria);

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Messenger/groups_manage_add.php')
        ->displayLabel();

    // COLUMNS
    $table->addColumn('name', __('Name'))->sortable();

    $table->addColumn('owner', __('Group Owner'))
        ->sortable(['surname', 'preferredName'])
        ->format(Format::using('name', ['', 'preferredName', 'surname', 'Staff', true, true]));

    $table->addColumn('count', __('Group Members'))->sortable();

    $table->addActionColumn()
        ->addParam('gibbonGroupID')
        ->format(function ($person, $actions) use ($guid) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Messenger/groups_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Messenger/groups_manage_delete.php');
        });

    echo $table->render($groups);

}
