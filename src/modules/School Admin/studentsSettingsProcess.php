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

$URL = array('q' => '/modules/School Admin/studentsSettings.php');

if (! $this->getSecurity()->isActionAccessible('/modules/School Admin/studentsSettings.php')) {
    $this->insertMessage('return.error.0');
    $this->redirect($URL);
} else {
    //Proceed!
    $studentAgreementOptions = array();
    foreach (explode(',', $_POST['studentAgreementOptions']) as $agreement) 
        $studentAgreementOptions[] = trim($agreement).',';
    $_POST['studentAgreementOptions'] = trim(implode(',', $studentAgreementOptions), ',');

    //Write to database
    $fail = false;

    if (! $this->config->setSettingByScope('enableStudentNotes', $_POST['enableStudentNotes'], 'Students' )) $fail = true;
    if (! $this->config->setSettingByScope('extendedBriefProfile', $_POST['extendedBriefProfile'], 'Students' )) $fail = true;
    if (! $this->config->setSettingByScope('studentAgreementOptions', $_POST['studentAgreementOptions'], 'School Admin' )) $fail = true;

    if ($fail) {
        $this->insertMessage('return.error.2');
        $this->redirect($URL);
    } else {
        //Success 0
        $this->session->getSystemSettings($this->pdo);
        $this->insertMessage('return.success.0', 'success');
        $this->redirect($URL);
    }
}
