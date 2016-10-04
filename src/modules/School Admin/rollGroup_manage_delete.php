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

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'Delete Roll Group' ;
	$trail->addTrail('Manage Roll Groups', array('q'=>'/modules/School Admin/rollGroup_manage.php','gibbonSchoolYearID'=>$_GET['gibbonSchoolYearID']));
	$trail->render($this);
	
	$this->h3('Delete Roll Group');
	$this->render('default.flash');

    //Check if school year specified
   if (empty($_GET['gibbonRollGroupID']) || empty($_GET['gibbonSchoolYearID'])) {
        $this->displayMessage('You have not specified one or more required parameters.');
    } else {
		$syObj = new \Gibbon\Record\schoolYear($this, $_GET['gibbonSchoolYearID']);
		$rGObj = $syObj->getRollGroup($_GET['gibbonRollGroupID']);

        if (! $rGObj->getSuccess()) {
            $this->displayMessage('The specified record cannot be found.');
        } else {

			$rGObj->action = false;
			$this->render('rollGroups.listStart', $rGObj);
			$this->render('rollGroups.listMember', $rGObj);
			$this->render('rollGroups.listEnd');
			$this->getForm(GIBBON_ROOT."modules/School Admin/rollGroup_manage_deleteProcess.php", array('gibbonSchoolYearID'=>$_GET['gibbonSchoolYearID'], 'gibbonRollGroupID'=>$_GET['gibbonRollGroupID']), true)
				->deleteForm();
        }
    }
}
?>