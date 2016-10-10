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

namespace Module\School_Admin ;

use Gibbon\core\post ;

if (! $this instanceof post) die();

$URL = array('q'=>'/modules/School Admin/activitySettings.php');

if (! $this->getSecurity()->isActionAccessible('/modules/School Admin/activitySettings.php')) {
    $this->insertMessage('return.error.0');
    $this->redirect($URL);
} else {
    //Proceed!
    if ($_POST['dateType'] != 'Term') {
        $_POST['maxPerTerm'] = 0;
    }
    $activityTypes = '';
    foreach (explode(',', $_POST['activityTypes']) as $type) {
        $activityTypes .= trim($type).',';
    }
    $_POST['activityTypes'] = substr($activityTypes, 0, -1);

    //Validate Inputs
    if (	empty($_POST['dateType'])
			|| empty($_POST['access'])
			|| empty($_POST['payment'])
			|| empty($_POST['enrolmentType'])
			|| empty($_POST['backupChoice'])
			|| empty($_POST['disableExternalProviderSignup'])
			|| empty($_POST['hideExternalProviderCost'])
		)
	{
        $this->insertMessage('return.error.3');
        $this->redirect($URL);
    } else {
        //Write to database
        $fail = false;

    	if (! $this->config->setSettingByScope('dateType', $_POST['dateType'], 'Activities' )) $fail = true;
    	if (! $this->config->setSettingByScope('maxPerTerm', $_POST['maxPerTerm'], 'Activities' )) $fail = true;
    	if (! $this->config->setSettingByScope('access', $_POST['access'], 'Activities' )) $fail = true;
    	if (! $this->config->setSettingByScope('payment', $_POST['payment'], 'Activities' )) $fail = true;
    	if (! $this->config->setSettingByScope('enrolmentType', $_POST['enrolmentType'], 'Activities' )) $fail = true;
    	if (! $this->config->setSettingByScope('backupChoice', $_POST['backupChoice'], 'Activities' )) $fail = true;
    	if (! $this->config->setSettingByScope('activityTypes', $_POST['activityTypes'], 'Activities' )) $fail = true;
    	if (! $this->config->setSettingByScope('disableExternalProviderSignup', $_POST['disableExternalProviderSignup'], 'Activities' )) $fail = true;
    	if (! $this->config->setSettingByScope('hideExternalProviderCost', $_POST['hideExternalProviderCost'], 'Activities' )) $fail = true;

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
