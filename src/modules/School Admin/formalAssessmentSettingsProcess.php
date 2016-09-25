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

use Gibbon\core\view ;

if (! $this instanceof view) die();

$URL = array('q'=>'/modules/School Admin/formalAssessmentSettings.php');

if (! $this->getSecurity()->isActionAccessible('/modules/School Admin/formalAssessmentSettings.php')) {
    $this->insertMessage('return.error.0');
    $this->redirect($URL);
} else {
    $internalAssessmentTypes = '';
	$types = explode(',', $_POST['internalAssessmentTypes']);
    foreach ($types as $q=>$type) {
        if (empty($type)) unset($types[$q]);
    }
    $internalAssessmentTypes = implode(',',$types);
    $yearGroupID = $_POST['gibbonYearGroupID'];
    $externalAssessmentID = $_POST['gibbonExternalAssessmentID'];
    $primaryExternalAssessmentByYearGroup = array();
    $count = 0;
    foreach ($yearGroupID as $year) {
        $set = false;
        if (isset($externalAssessmentID[$count]) and $externalAssessmentID[$count] != '') {
            if (isset($_POST["category$count"]) && $_POST["category$count"] != '') {
				$primaryExternalAssessmentByYearGroup[$year] = $externalAssessmentID[$count].'-'.$_POST["category$count"];
				$set = true;
            }
        }
        if (! $set) {
            $primaryExternalAssessmentByYearGroup[$year] = null;
        }
        ++$count;
    }

    //Validate Inputs
    if (empty($internalAssessmentTypes)) {
        $this->insertMessage('return.error.3');
        $this->redirect($URL);
    } else {
        //Write to database
        $fail = false;

        //Update internal assessment fields
		if (! $this->config->setSettingByScope('internalAssessmentTypes', $internalAssessmentTypes, 'Formal Assessment')) $fail = true ;

        //Update external assessment fields
		if (! $this->config->setSettingByScope('primaryExternalAssessmentByYearGroup', serialize($primaryExternalAssessmentByYearGroup), 'School Admin')) $fail = true ;

        if ($fail) {
            $this->insertMessage('return.error.2');
            $this->redirect($URL);
        } else {
            $this->session->getSystemSettings($this->pdo);
            $this->insertMessage('return.success.0','success');
            $this->redirect($URL);
        }
    }
}
