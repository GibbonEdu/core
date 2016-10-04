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

$URL = array('q'=>'/modules/School Admin/resourceSettings.php');

if (! $this->getSecurity()->isActionAccessible('/modules/School Admin/resourceSettings.php')) {
    $this->insertMessage('return.error.0');
    $this->redirect($URL);
} else {
    //Proceed!
    $categories = '';
    foreach (explode(',', $_POST['categories']) as $category) {
        $categories .= trim($category).',';
    }
    $categories = rtrim($categories, ',');
    $purposesGeneral = '';
    foreach (explode(',', $_POST['purposesGeneral']) as $purpose) {
        $purposesGeneral .= trim($purpose).',';
    }
    $purposesGeneral = rtrim($purposesGeneral, ',');
    $purposesRestricted = '';
    foreach (explode(',', $_POST['purposesRestricted']) as $purpose) {
        $purposesRestricted .= trim($purpose).',';
    }
    $purposesRestricted = rtrim($purposesRestricted, ',');

    //Validate Inputs
    if ($categories == '' or $purposesGeneral == '') {
        $this->insertMessage('return.error.3');
        $this->redirect($URL);
    } else {
        //Write to database
        $fail = false;

        if (! $this->config->setSettingByScope('categories', $categories, 'Resources' )) $fail = true;
        if (! $this->config->setSettingByScope('purposesGeneral', $purposesGeneral, 'Resources' )) $fail = true;
        if (! $this->config->setSettingByScope('purposesRestricted', $purposesRestricted, 'Resources' )) $fail = true;

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
