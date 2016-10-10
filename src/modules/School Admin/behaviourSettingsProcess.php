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

$URL = array('q'=>'/modules/School Admin/behaviourSettings.php');

if (! $this->getSecurity()->isActionAccessible('/modules/School Admin/behaviourSettings.php')) {
    $this->insertMessage('return.error.0');
    $this->redirect($URL);
} else {
    //Proceed!
    $positiveDescriptors = '';
    $negativeDescriptors = '';
    if ($_POST['enableDescriptors'] == 'Y') {
        foreach (explode(',', $_POST['positiveDescriptors']) as $descriptor) {
            $positiveDescriptors .= trim($descriptor).',';
        }
        $_POST['positiveDescriptors'] = trim($positiveDescriptors, ',');

        foreach (explode(',', $_POST['negativeDescriptors']) as $descriptor) {
            $negativeDescriptors .= trim($descriptor).',';
        }
        $_POST['negativeDescriptors'] = trim($negativeDescriptors, ',');
    }
    $levels = '';
    if ($_POST['enableLevels'] == 'Y') {
        foreach (explode(',', $_POST['levels']) as $level) {
            $levels .= trim($level).',';
        }
        $_POST['levels'] = trim($levels, ',');
    }

    //Validate Inputs
    if (	empty($_POST['enableDescriptors']) 
			|| empty($_POST['enableLevels'])
			|| (empty($_POST['positiveDescriptors']) && $_POST['enableDescriptors'] == 'Y') 
			|| (empty($_POST['negativeDescriptors']) && $_POST['enableDescriptors'] == 'Y') 
			|| (empty($_POST['levels']) && $_POST['enableLevels'] == 'Y') 
			|| (
				(empty($_POST['behaviourLettersLetter1Count']) 
				|| empty($_POST['behaviourLettersLetter1Text']) 
				|| empty($_POST['behaviourLettersLetter2Count']) 
				|| empty($_POST['behaviourLettersLetter2Text']) 
				|| empty($_POST['behaviourLettersLetter3Count']) 
				|| empty($_POST['behaviourLettersLetter3Text'])
			) && $_POST['enableBehaviourLetters'] == 'Y')) {
        $this->insertMessage('return.error.3');
        $this->redirect($URL);
    } else {
        //Write to database
        $fail = false;

		if (! $this->config->setSettingByScope('enableDescriptors', $_POST['enableDescriptors'], 'Behaviour' )) $fail = true;
        if ($_POST['enableDescriptors'] == 'Y') {
			if (! $this->config->setSettingByScope('positiveDescriptors', $_POST['positiveDescriptors'], 'Behaviour' )) $fail = true;
			if (! $this->config->setSettingByScope('negativeDescriptors', $_POST['negativeDescriptors'], 'Behaviour' )) $fail = true;
        }

		if (! $this->config->setSettingByScope('enableLevels', $_POST['enableLevels'], 'Behaviour' )) $fail = true;
        if ($_POST['enableLevels'] == 'Y') 
			if (! $this->config->setSettingByScope('levels', $_POST['levels'], 'Behaviour' )) $fail = true;

		if (! $this->config->setSettingByScope('enableBehaviourLetters', $_POST['enableBehaviourLetters'], 'Behaviour' )) $fail = true;
        if ($_POST['enableBehaviourLetters'] == 'Y') {
			if (! $this->config->setSettingByScope('behaviourLettersLetter1Count', $_POST['behaviourLettersLetter1Count'], 'Behaviour' )) $fail = true;
			if (! $this->config->setSettingByScope('behaviourLettersLetter1Text', $_POST['behaviourLettersLetter1Text'], 'Behaviour' )) $fail = true;
			if (! $this->config->setSettingByScope('behaviourLettersLetter2Count', $_POST['behaviourLettersLetter2Count'], 'Behaviour' )) $fail = true;
			if (! $this->config->setSettingByScope('behaviourLettersLetter2Text', $_POST['behaviourLettersLetter2Text'], 'Behaviour' )) $fail = true;
			if (! $this->config->setSettingByScope('behaviourLettersLetter3Count', $_POST['behaviourLettersLetter3Count'], 'Behaviour' )) $fail = true;
			if (! $this->config->setSettingByScope('behaviourLettersLetter3Text', $_POST['behaviourLettersLetter3Text'], 'Behaviour' )) $fail = true;
		}

		if (! $this->config->setSettingByScope('policyLink', $_POST['policyLink'], 'Behaviour' )) $fail = true;

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
