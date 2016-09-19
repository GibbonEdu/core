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

use Gibbon\core\view ;
use Gibbon\core\trans ;
use Gibbon\Record\alarm ;

if (! $this instanceof view) die();

$URL = GIBBON_URL.'index.php?q=/modules/System Admin/alarm.php';

if (! $this->getSecurity()->isActionAccessible('/modules/System Admin/alarm.php')) {
    $this->insertMessage('return.error.0');
    $this->redirect($URL);
} else {
    //Proceed!
    $alarmID = '';
    if (isset($_GET['gibbonAlarmID'])) {
        $alarmID = $_GET['gibbonAlarmID'];
    }

    //Validate Inputs
    if (empty($alarmID)) {
        $this->insertMessage('return.error.3');
        $this->redirect($URL);
    } else {
        $fail = false;

        //DEAL WITH ALARM SETTING
        //Write setting to database
        if (! $this->config->setSettingByScope('alarm', 'None', 'System')) $fail = true;

        //Deal with alarm record
		$aObj = new alarm($this, $alarmID);
		$aObj->setField('timestampEnd', date('Y-m-d H:i:s'));
		$aObj->setField('status', 'Past');
        if (! $aObj->writeRecord()) $fail = true;

        if ($fail) {
            $this->insertMessage('return.error.2');
            $this->redirect($URL);
        } else {
            $this->session->getSystemSettings($this->pdo);
            $this->insertMessage('return.success.0', 'success');
            $this->redirect($URL);
        }
    }
}
