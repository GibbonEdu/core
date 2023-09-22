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

use Gibbon\Domain\User\RoleGateway;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;

if (isActionAccessible($guid, $connection2, '/modules/User Admin/role_manage_view.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Manage Roles'), 'role_manage.php')
        ->add(__('View Role'));

    $gibbonRoleID = $_GET['gibbonRoleID'] ?? '';

    if (empty($gibbonRoleID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $roleGateway = $container->get(RoleGateway::class);
    $role = $roleGateway->getByID($gibbonRoleID);

    // CRITERIA
    $criteria = $roleGateway->newQueryCriteria(true)
        ->sortBy(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
        ->filterBy('status:full')
        ->fromPOST();

    $actions = $roleGateway->selectActionsByRole($gibbonRoleID)->fetchGrouped();
    $users = $roleGateway->queryUsersByRole($criteria, $gibbonRoleID);

    // DATA TABLE
    $table = DataTable::createPaginated('roleView', $criteria);
    $table->setTitle(__('Users').': '.$role['name']);

    $table->modifyRows(function ($person, $row) {
        if (!empty($person['status']) && $person['status'] != 'Full') $row->addClass('error');
        if ($person['canLogin'] == 'N') $row->addClass('warning');
        return $row;
    });

    $table->addMetaData('filterOptions', [
        'status:full'     => __('Status').': '.__('Full'),
        'status:left'     => __('Status').': '.__('Left'),
        'status:expected' => __('Status').': '.__('Expected'),
        'primaryRole:y'   => __('Primary Role').': '.__('Yes'),
        'primaryRole:n'   => __('Primary Role').': '.__('No'),
    ]);

    $table->addColumn('image_240', __('Photo'))
        ->context('primary')
        ->width('10%')
        ->notSortable()
        ->format(Format::using('userPhoto', ['image_240', 'sm']));

    $table->addColumn('fullName', __('Name'))
        ->context('primary')
        ->width('30%')
        ->sortable(['surname', 'preferredName'])
        ->format(function ($person) {
            return Format::name('', $person['preferredName'], $person['surname'], 'Staff', true, true)
                .'<br/>'.Format::small(Format::userStatusInfo($person));
        });

    $table->addColumn('primaryRole', __('Primary Role'))
        ->format(Format::using('yesNo', 'primaryRole'));

    $table->addColumn('allRoles', __('All Roles'))
        ->format(function ($person) {
            $allRoles = explode(',', $person['allRoles']);
            $allRoles = array_map(function ($role) {
                return __($role);
            }, $allRoles);
            return implode('<br/>', $allRoles);
        });

    $table->addColumn('canLogin', __('Can Login'))
        ->format(Format::using('yesNo', 'canLogin'));

    // ACTIONS
    if (isActionAccessible($guid, $connection2, '/modules/User Admin/user_manage.php')) {
        $table->addActionColumn()
            ->addParam('gibbonPersonID')
            ->format(function ($person, $actions) {
                $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/User Admin/user_manage_edit.php');
            });
    }

    $page->writeFromTemplate('roleActions.twig.html', ['actions' => $actions, 'actionCount' => count($actions), 'role' => $role]);

    echo $table->render($users);
}
