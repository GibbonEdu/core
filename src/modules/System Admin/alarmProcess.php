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

namespace Module\System_Admin ;

use Gibbon\core\post ;
use Gibbon\core\trans ;
use Gibbon\core\fileManager ;
use Gibbon\Record\alarm ;

if (!$this instanceof post) die();

$URL = GIBBON_URL.'index.php?q=/modules/System Admin/alarm.php';

if (! $this->getSecurity()->isActionAccessible('/modules/System Admin/alarm.php')) {
    $this->insertMessage('return.error.0');
    $this->redirect($URL);
} else {
    //Proceed!
    $alarm = $_POST['alarm'];
    $attachmentCurrent = $_POST['attachmentCurrent'];
    $alarmCurrent = $_POST['alarmCurrent'];

    //Validate Inputs
    if (! in_array($alarm, array('None', 'General', 'Lockdown', 'Custom')) && ! empty($alarmCurrent)) {
        $this->insertMessage('return.error.3');
        $this->redirect($URL);
    } else {
        $fail = false;

        //DEAL WITH CUSTOM SOUND SETTING
        $time = time();
        //Move attached file, if there is one
        if (! empty($_FILES['file']['tmp_name'])) {
            //Check for folder in uploads based on today's date
			$fm = new fileManager($this);
			$fm1 = new fileManager($this->view);
			if (! $fm1->fileManage('file1', 'alarmSound')) $fail = true ;
        } else {
            $attachment = $attachmentCurrent;
        }

        //Write setting to database
        if (! $this->config->setSettingByScope('customAlarmSound', $attachment, 'System Admin')) $fail = true;

        //DEAL WITH ALARM SETTING
        //Write setting to database
		if (! $this->config->setSettingByScope('alarm', $alarm, 'System')) $fail = true;
        //Check for existing alarm
        $checkFail = false;
		$al = new alarm($this);
		$x = $al->findBy(array('status' => 'Current'));
        if (! $al->getSuccess()) $checkFail = true;

        //Alarm is being turned on, so insert new record
        if (in_array($alarm, array('General', 'Lockdown', 'Custom'))) {
            if ($checkFail) {
                $fail = true;
            } else {
				//Write alarm to database
				$al->setField('type', $alarm);
				$al->setField('gibbonPersonID', $this->session->get('gibbonPersonID'));
				$al->setField('timestampStart', date('Y-m-d H:i:s'));
				$al->setField('status', 'Current');
				if (! $al->writeRecord()) $fail = true;
            }
        } elseif ($alarmCurrent != $alarm) {
            if ($al->rowCount() == 1) {
				$al->setField('timestampEnd', date('Y-m-d H:i:s'));
				$al->setField('status', 'Past');
                if (! $al->writeRecord()) $fail = true;
            } else {
                $fail = true;
            }
        }

        if ($fail == true) {
            $this->insertMessage('return.error.2');
            $this->redirect($URL);
        } else {
            $this->session->getSystemSettings($this->pdo);
            $this->insertMessage('return.success.0', 'success');
            $this->redirect($URL);
        }
    }
}
