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

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/School Admin/attendanceSettings_manage_add.php') == false) {
    //Access denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Attendance Settings'), 'attendanceSettings.php')
        ->add(__('Add Attendance Code'));

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/School Admin/attendanceSettings_manage_add.php&gibbonDepartmentID='.$_GET['editID'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    $form = Form::create('attendanceCode', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/attendanceSettings_manage_addProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Must be unique.'));
        $row->addTextField('name')->required()->maxLength(30);

    $row = $form->addRow();
        $row->addLabel('nameShort', __('Short Name'))->description(__('Must be unique.'));
        $row->addTextField('nameShort')->required()->maxLength(4);

    $directions = array(
        'In'     => __('In Class'),
        'Out' => __('Out of Class'),
    );
    $row = $form->addRow();
        $row->addLabel('direction', __('Direction'));
        $row->addSelect('direction')->required()->fromArray($directions);

    $scopes = array(
        'Onsite'         => __('Onsite'),
        'Onsite - Late'  => __('Onsite - Late'),
        'Offsite'        => __('Offsite'),
        'Offsite - Left' => __('Offsite - Left'),
        'Offsite - Late' => __('Offsite - Late'),
    );
    $row = $form->addRow();
        $row->addLabel('scope', __('Scope'));
        $row->addSelect('scope')->required()->fromArray($scopes);

    $row = $form->addRow();
        $row->addLabel('sequenceNumber', __('Sequence Number'));
        $row->addSequenceNumber('sequenceNumber', 'gibbonAttendanceCode')->required()->maxLength(3);

    $row = $form->addRow();
        $row->addLabel('active', __('Active'));
        $row->addYesNo('active')->required();

    $row = $form->addRow();
        $row->addLabel('reportable', __('Reportable'));
        $row->addYesNo('reportable')->required();

    $row = $form->addRow();
        $row->addLabel('prefill', __('Prefillable'))->description(__('Can this code prefill when taking attendance?'));
        $row->addYesNo('prefill')->required();

    $row = $form->addRow();
        $row->addLabel('future', __('Allow Future Use'))->description(__('Can this code be used in Set Future Absence?'));
        $row->addYesNo('future')->required();

    $row = $form->addRow();
        $row->addLabel('gibbonRoleIDAll', __('Available to Roles'))->description(__('Controls who can use this code.'));
        $row->addSelectRole('gibbonRoleIDAll')->selectMultiple();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
