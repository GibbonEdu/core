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
use Gibbon\FileUploader;
use Gibbon\Domain\System\SettingGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/alarm.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Sound Alarm'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Get list of acceptable file extensions
    $fileUploader = new FileUploader($pdo, $gibbon->session);
    $fileUploader->getFileExtensions('Audio');

    // Alarm Types
    $alarmTypes = array(
        'None'     => __('None'),
        'General'  => __('General'),
        'Lockdown' => __('Lockdown'),
        'Custom'   => __('Custom'),
    );

    $form = Form::create('alarmSettings', $gibbon->session->get('absoluteURL').'/modules/'.$gibbon->session->get('module').'/alarmProcess.php');
    
    $settingGateway = $container->get(SettingGateway::class);
    
    $settingAlarmSound = $settingGateway->getSettingByScope('System Admin', 'customAlarmSound', true);

    $form->addHiddenValue('address', $gibbon->session->get('address'));
    
    $row = $form->addRow();
        $label = $row->addLabel('file', __($settingAlarmSound['nameDisplay']))->description(__($settingAlarmSound['description']));
        if (!empty($settingAlarmSound['value'])) $label->append(__('Will overwrite existing attachment.'));

        $file = $row->addFileUpload('file')
                    ->accepts($fileUploader->getFileExtensionsCSV())
                    ->setAttachment('attachmentCurrent', $gibbon->session->get('absoluteURL'), $settingAlarmSound['value']);

    $settingAlarm = $settingGateway->getSettingByScope('System', 'alarm', true);
    $form->addHiddenValue('alarmCurrent', $settingAlarm['value']);

    $row = $form->addRow();
        $row->addLabel($settingAlarm['name'], __($settingAlarm['nameDisplay']))->description(__($settingAlarm['description']));
        $row->addSelect($settingAlarm['name'])->fromArray($alarmTypes)->selected($settingAlarm['value'])->required();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
