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

$URL = array('q' => "/modules/School Admin/spaceSettings.php");

if (! $this->getSecurity()->isActionAccessible('/modules/School Admin/spaceSettings.php')) {
    $this->insertMessage('return.error.0');
    $this->redirect($URL);
} else {
    $facilityTypes = '';
    foreach (explode(',', $_POST['facilityTypes']) as $type) {
        $facilityTypes .= trim($type).',';
    }
    $_POST['facilityTypes'] = substr($facilityTypes, 0, -1);

    //Validate Inputs
    if (empty($_POST['facilityTypes'])) {
        $this->insertMessage('return.error.3');
        $this->redirect($URL);
    } else {
        //Write to database

        if (! $this->config->setSettingByScope('facilityTypes', $_POST['facilityTypes'], 'School Admin' )) {
            $this->insertMessage('return.error.2');
            $this->redirect($URL);
        } else {
            $this->session->getSystemSettings($this->pdo);
            $this->insertMessage('return.success.0', 'success');
            $this->redirect($URL);
        }
    }
}
