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
use Gibbon\Record\externalAssessment ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'Delete External Assessment';
	$trail->addTrail('Manage External Assessments', '/index.php?q=/modules/School Admin/externalAssessments_manage.php');
	$trail->render($this);
	
	$this->render('default.flash');
	
	$this->h2('Delete External Assessment');
	
	//Check if school year specified
	$externalAssessmentID = $_GET["gibbonExternalAssessmentID"] ;
	if (intval($externalAssessmentID) < 1) {
		$this->displayMessage('You have not specified one or more required parameters.') ;
	}
	else {
		$eaObj = new externalAssessment($this, $externalAssessmentID);
		if (! $eaObj->getSuccess()) {
			$this->displayMessage('The specified record cannot be found.') ;
		}
		else {
			$el = new \stdClass();
			$eaObj->action = $el->action = false ;
			$this->render('externalAssessment.listStart', $el);
			$this->render('externalAssessment.listMember', $eaObj);
			$this->render('externalAssessment.listEnd');
			$this->getForm(GIBBON_ROOT . 'modules/School Admin/externalAssessments_manage_deleteProcess.php', array('gibbonExternalAssessmentID' => $externalAssessmentID), true)
				->deleteForm();
		}
	}
}
