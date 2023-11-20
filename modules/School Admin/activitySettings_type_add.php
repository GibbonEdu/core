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

use Gibbon\Forms\Form;
use Gibbon\Domain\System\SettingGateway;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/activitySettings_type_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Activity Settings'), 'activitySettings.php')
        ->add(__('Add'));

    if (isset($_GET['editID'])) {
        $editLink = $session->get('absoluteURL').'/index.php?q=/modules/School Admin/activitySettings_type_edit.php&gibbonActivityTypeID='.$_GET['editID'];
        $page->return->setEditLink($editLink);
    }

    $settingGateway = $container->get(SettingGateway::class);
    
    $form = Form::create('activityType', $session->get('absoluteURL').'/modules/School Admin/activitySettings_type_addProcess.php');
    $form->addHiddenValue('address', $session->get('address'));

    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Must be unique.'));
        $row->addTextField('name')->required()->maxLength(60);

    $row = $form->addRow();
        $row->addLabel('description', __('Description'));
        $row->addTextArea('description');

    $access = $settingGateway->getSettingByScope('Activities', 'access');
    $accessTypes = array('None' => __('None'), 'View' => __('View'), 'Register' => __('Register'));
    $row = $form->addRow();
        $row->addLabel('access', __('Access'));
        $row->addSelect('access')->fromArray($accessTypes)->required()->selected($access);

    $enrolmentType = $settingGateway->getSettingByScope('Activities', 'enrolmentType');
    $enrolmentTypes = array('Competitive' => __('Competitive'), 'Selection' => __('Selection'));
    $row = $form->addRow();
        $row->addLabel('enrolmentType', __('Enrolment Type'))->description(__('Enrolment process type'));
        $row->addSelect('enrolmentType')->fromArray($enrolmentTypes)->required()->selected($enrolmentType);

    $row = $form->addRow();
        $row->addLabel('maxPerStudent', __('Max per Student'))->description(__('The most a student can sign up for in this activity type. Set to 0 for unlimited.'));
        $row->addNumber('maxPerStudent')->minimum(0)->maximum(99)->setValue('0');

    $row = $form->addRow();
        $row->addLabel('waitingList', __('Waiting List'))->description(__('Should students be placed on a waiting list if the enrolled activity is full.'));
        $row->addYesNo('waitingList')->required()->selected('Y');

    $backupChoice = $settingGateway->getSettingByScope('Activities', 'backupChoice');
    $row = $form->addRow();
        $row->addLabel('backupChoice', __('Backup Choice'))->description(__('Allow students to choose a backup, in case enrolled activity is full.'));
        $row->addYesNo('backupChoice')->required()->selected($backupChoice);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
