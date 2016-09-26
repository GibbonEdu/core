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
use Gibbon\Record\scaleGrade ;

if (! $this instanceof view ) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'Delete Grade' ;
	$trail->addTrail('Manage Grade Scales', array('q' => '/modules/School Admin/gradeScales_manage.php'));
	$trail->addTrail('Edit Grade Scale', array('q' => '/modules/School Admin/gradeScales_manage_edit.php', 'gibbonScaleID' => $_GET['gibbonScaleID']));
	$trail->render($this);
	
	$this->render('default.flash');
	$this->h2('Delete Grade');

	//Check if school year specified
	$scaleGradeID = $_GET["gibbonScaleGradeID"] ;
	$scaleID = $_GET["gibbonScaleID"] ;
	if (empty($scaleGradeID) || empty($scaleID)) {
		$this->displayMessage("You have not specified one or more required parameters.") ;
	}
	else {
		$sgObj = new scaleGrade($this, $scaleGradeID);


		if (! $sgObj->getSuccess()) {
			$this->displayMessage("The specified record cannot be found.") ;
		}
		else {
			$el = new \stdClass();
			$el->action = false ;
			$this->render('scale.grade.listStart', $el);
			$sgObj->action = false ;
			$this->render('scale.grade.listMember', $sgObj);
			$this->render('scale.grade.listEnd', $el);
			$this->getForm(GIBBON_ROOT . 'modules/School Admin/gradeScales_manage_edit_grade_deleteProcess.php', array('gibbonScaleGradeID' => $scaleGradeID, 'gibbonScaleID' => $scaleID), true)
				->deleteForm();
		}
	}
}
