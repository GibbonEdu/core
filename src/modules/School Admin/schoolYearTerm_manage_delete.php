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
use Gibbon\Record\schoolYearTerm ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->addtrail('Manage Terms', array('q'=>'/modules/School Admin/schoolYearTerm_manage.php'));
	$trail->trailEnd = 'Delete Term';
	$trail->render($this);
	
	$this->render('default.flash');
	$this->h2('Delete Term');
	//Check if school year term specified
	$schoolYearTermID=$_GET["gibbonSchoolYearTermID"] ;
	if (empty($schoolYearTermID)) {
		$this->displayMessage( "You have not specified one or more required parameters.") ;
	}
	else {
		if ($termObj = new schoolYearTerm($this, intval($schoolYearTermID)))
		{
			//Let's go!
			$termObj->action = false;
			$this->render('terms.listStart', $termObj);
			$this->render('terms.listMember', $termObj);
			$this->render('terms.listEnd');
			$this->getForm(null, array('q'=>'modules/School Admin/schoolYearTerm_manage_deleteProcess.php','gibbonSchoolYearTermID'=>$schoolYearTermID), true)
				->deleteForm();
		}
	}
}
