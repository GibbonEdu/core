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
use Gibbon\Domain\User\DistrictGateway;

if (isActionAccessible($guid, $connection2, '/modules/User Admin/district_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Manage Districts'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $districtGateway = $container->get(DistrictGateway::class);

    // QUERY
    $criteria = $districtGateway->newQueryCriteria()
        ->sortBy('name')
        ->fromPOST();

    $districts = $districtGateway->queryDistricts($criteria);

    // DATA TABLE
    $table = DataTable::createPaginated('districtManage', $criteria);

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/User Admin/district_manage_add.php')
        ->displayLabel();

    $table->addColumn('name', __('Name'));

    $table->addActionColumn()
        ->addParam('gibbonDistrictID')
        ->format(function ($row, $actions) {
            $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/User Admin/district_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                ->setURL('/modules/User Admin/district_manage_delete.php');
        });

    echo $table->render($districts);
}
