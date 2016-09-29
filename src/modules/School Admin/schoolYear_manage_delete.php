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
use Gibbon\Record\schoolYear ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->addTrail('Manage School Years' , "/index.php?q=/modules/School Admin/schoolYear_manage.php");
	$trail->trailEnd = 'Delete School Year';
	$trail->render($this);
	
	$this->render('default.flash');
	$this->h2('Delete School Year');
	
	//Check if school year specified
	$schoolYearID=$_GET["gibbonSchoolYearID"] ;
	if (intval($schoolYearID) == 0) {
		$this->displayMessage("You have not specified one or more required parameters.");
	}
	else
	{
		if ($year = new schoolYear($this, $schoolYearID)) {
			$year->action = false;
			$this->render('schoolYear.listStart', $year);
			$this->render('schoolYear.listMember', $year);
			$this->render('schoolYear.listEnd');
			if ($year->canDelete()) {
				$this->getForm(GIBBON_ROOT.'modules/School Admin/schoolYear_manage_deleteProcess.php', array('gibbonSchoolYearID'=>$schoolYearID), true)
					->deleteForm();
			} else
				$this->displayMessage("This record is locked by the system and is not to be deleted!", 'warning');
		}
		else
			$this->displayMessage("return.error.2");
	}
}
