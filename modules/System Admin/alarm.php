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

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/alarm.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Sound Alarm'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Get list of acceptable file extensions
    $fileUploader = new FileUploader($pdo, $gibbon->session);
    $fileUploader->getFileExtensions('Audio');

    // Alram Types
    $alarmTypes = array(
        'None'     => __('None'),
        'General'  => __('General'),
        'Lockdown' => __('Lockdown'),
        'Custom'   => __('Custom'),
    );

    $form = Form::create('alarmSettings', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/alarmProcess.php');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $setting = getSettingByScope($connection2, 'System Admin', 'customAlarmSound', true);

    $row = $form->addRow();
        $label = $row->addLabel('file', __($setting['nameDisplay']))->description(__($setting['description']));
        if (!empty($setting['value'])) $label->append(__('Will overwrite existing attachment.'));

        $file = $row->addFileUpload('file')
                    ->accepts($fileUploader->getFileExtensionsCSV())
                    ->setAttachment('attachmentCurrent', $_SESSION[$guid]['absoluteURL'], $setting['value']);

    $setting = getSettingByScope($connection2, 'System', 'alarm', true);
    $form->addHiddenValue('alarmCurrent', $setting['value']);

    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])->fromArray($alarmTypes)->selected($setting['value'])->isRequired();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
