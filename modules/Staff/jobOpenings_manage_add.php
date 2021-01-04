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
use Gibbon\Domain\User\RoleGateway;

if (isActionAccessible($guid, $connection2, '/modules/Staff/jobOpenings_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Job Openings'), 'jobOpenings_manage.php')
        ->add(__('Add Job Opening'));

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Staff/jobOpenings_manage_edit.php&gibbonStaffJobOpeningID='.$_GET['editID'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/jobOpenings_manage_addProcess.php');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $types = array('Teaching' => __('Teaching'), 'Support' => __('Support'));
    $row = $form->addRow();
        $row->addLabel('type', __('Type'));
        $row->addSelect('type')->fromArray($types)->placeholder()->required();

    $row = $form->addRow();
        $row->addLabel('jobTitle', __('Job Title'));
        $row->addTextField('jobTitle')->maxlength(100)->required();

    $row = $form->addRow();
        $row->addLabel('dateOpen', __('Opening Date'));
        $row->addDate('dateOpen')->required();

    $row = $form->addRow();
        $row->addLabel('active', __('Active'));
        $row->addYesNo('active')->required();

    $jobOpeningDescriptionTemplate = getSettingByScope($connection2, 'Staff', 'jobOpeningDescriptionTemplate');
    $row = $form->addRow();
        $column = $row->addColumn();
        $column->addLabel('description', __('Description'));
        $column->addEditor('description', $guid)->setRows(20)->showMedia()->setValue($jobOpeningDescriptionTemplate)->required();

    $row = $form->addRow();
    $row->addFooter();
    $row->addSubmit();

    echo $form->getOutput();
}
