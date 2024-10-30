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

use Gibbon\Domain\System\AlarmGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/alarm.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/alarm.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $alarm = $_POST['alarm'] ?? '';
    $alarmCurrent = $_POST['alarmCurrent'] ?? '';

    //Validate Inputs
    if ($alarm != 'None' and $alarm != 'General' and $alarm != 'Lockdown' and $alarm != 'Custom' and $alarmCurrent != '') {
        $URL .= '&return=error3';
        header("Location: {$URL}");
    } else {
        //DEAL WITH CUSTOM SOUND SETTING
        $alarmGateway = $container->get(AlarmGateway::class);
        $settingGateway = $container->get(SettingGateway::class);

        $time = time();
        $attachmentCurrent = $settingGateway->getSettingByScope('System Admin', 'customAlarmSound');

        //Move attached file, if there is one
        if (!empty($_FILES['file']['tmp_name'])) {
            $fileUploader = new Gibbon\FileUploader($pdo, $session);

            $file = (isset($_FILES['file']))? $_FILES['file'] : null;

            // Upload the file, return the /uploads relative path
            $attachment = $fileUploader->uploadFromPost($file, 'alarmSound');

            if (empty($attachment)) {
                $URL .= '&return=error1';
                header("Location: {$URL}");
                exit;
            }
        } else {
            // Remove the attachment if it has been deleted, otherwise retain the original value
            $attachment = empty($_POST['attachmentCurrent']) ? '' : $attachmentCurrent;
        }
        
        //Write setting to database
        $dataWhere = ['scope' => 'System Admin', 'name' => 'customAlarmSound'];
        $settingGateway->updateWhere($dataWhere, ['value' => $attachment]);

        //DEAL WITH ALARM SETTING
        //Write setting to database
        $dataWhereAdmin = ['scope' => 'System', 'name' => 'alarm'];
        $settingGateway->updateWhere($dataWhereAdmin, ['value' => $alarm]);

        //Check for existing alarm
        $alarmTest = $alarmGateway->selectBy(['status' => 'Current'])->fetch();

        //Alarm is being turned on, so insert new record
        if ($alarm == 'General' or $alarm == 'Lockdown' or $alarm == 'Custom') {
            if (empty($alarmTest)) {
                //Write alarm to database
                $data = ['type' => $alarm, 'status' => 'Current', 'gibbonPersonID' => $session->get('gibbonPersonID'), 'timestampStart' => date('Y-m-d H:i:s')];
                $alarmGateway->insert($data);
            } else {
                $alarmGateway->updateWhere(['gibbonAlarmID' => $alarmTest['gibbonAlarmID']], ['type' => $alarm]);
            }
        } elseif ($alarmCurrent != $alarm) {
            $alarmGateway->update($alarmTest['gibbonAlarmID'], ['status' => 'Past', 'timestampEnd' => date('Y-m-d H:i:s')]);
        }

        getSystemSettings($guid, $connection2);
        $URL .= '&return=success0';
        header("Location: {$URL}");
    }
}
