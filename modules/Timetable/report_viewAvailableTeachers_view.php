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

use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Staff\StaffGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable/report_viewAvailableTeachers.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {

    $date = $_GET['date'] ?? '';
    $period = $_GET['period'] ?? '';
    $gibbonPersonIDList = $_GET['ids'] ?? [];

    $staffGateway = $container->get(StaffGateway::class);
    $teachers = $staffGateway->selectStaffByID($gibbonPersonIDList)->fetchAll();

    // DATA TABLE
    $table = DataTable::create('teacherList');
    $table->setTitle(Format::dateReadable($date). ' - '. $period);
    $table->setDescription(__('View Available Teachers'));

    // COLUMNS
    $table->addColumn('image_240', __('Photo'))
        ->context('primary')
        ->width('10%')
        ->notSortable()
        ->format(Format::using('userPhoto', ['image_240', 'sm']));

    $table->addColumn('fullName', __('Name'))
        ->context('primary')
        ->width('30%')
        ->sortable(['surname', 'preferredName'])
        ->format(Format::using('nameLinked', ['gibbonPersonID', 'title', 'preferredName', 'surname', 'Staff', true, true]));

    $table->addColumn('jobTitle', __('Job Title'));

    $table->addColumn('username', __('Username'))->context('primary');

    echo $table->render($teachers);
}
