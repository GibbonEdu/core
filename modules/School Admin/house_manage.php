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
use Gibbon\Domain\School\HouseGateway;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/house_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Manage Houses'));

    $houseGateway = $container->get(HouseGateway::class);

    // QUERY
    $criteria = $houseGateway->newQueryCriteria(true)
        ->sortBy(['name'])
        ->fromPOST();

    $houses = $houseGateway->queryHouses($criteria);

    // DATA TABLE
    $table = DataTable::createPaginated('houseManage', $criteria);

    $table->addHeaderAction('assign', __('Assign Houses'))
        ->setIcon('attendance')
        ->setURL('/modules/School Admin/house_manage_assign.php')
        ->displayLabel(__('Assign Houses'))
        ->append('&nbsp|&nbsp');

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/School Admin/house_manage_add.php')
        ->displayLabel();

    $table->addColumn('logo', __('Logo'))
    ->notSortable()
    ->format(function($values) use ($session) {
        $return = null;
        $return .= ($values['logo'] != '') ? "<img class='user' style='max-width: 75px' src='".$session->get('absoluteURL').'/'.$values['logo']."'/>":"<img class='user' style='max-width: 75px' src='".$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName')."/img/anonymous_240_square.jpg'/>";
        return $return;
    });
    $table->addColumn('name', __('Name'));
    $table->addColumn('nameShort', __('Short Name'));

    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonHouseID')
        ->format(function ($facilities, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/School Admin/house_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/School Admin/house_manage_delete.php');
        });

    echo $table->render($houses);
}
