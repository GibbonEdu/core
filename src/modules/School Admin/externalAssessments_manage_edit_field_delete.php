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

use Gibbon\core\helper ;
use Gibbon\Record\externalAssessmentField ;
use Gibbon\core\view ;
use Gibbon\core\trans ;

if (! $this instanceof view ) die();

if ($this->getSecurity()->isActionAccessible()) {
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'Delete Grade' ;
	$trail->addTrail('Manage External Assessments', '/index.php?q=/modules/School Admin/externalAssessments_manage.php');
	$trail->addTrail('Edit External Assessment', '/index.php?q=/modules/School Admin/externalAssessments_manage_edit.php&gibbonExternalAssessmentID=' . $_GET['gibbonExternalAssessmentID']);
	$trail->render($this);

	$this->render('default.flash');

	$this->h2('Delete Grade');

	$externalAssessmentFieldID = $_GET['gibbonExternalAssessmentFieldID'] ;
	$externalAssessmentID = $_GET['gibbonExternalAssessmentID'] ;
	if (empty($externalAssessmentFieldID) || empty($externalAssessmentID)) {
		$this->displayMessage('You have not specified one or more required parameters.') ;
	}
	else {
		$eafObj = new externalAssessmentField($this, $externalAssessmentFieldID);

		if (intval($eafObj->getField('gibbonExternalAssessmentID')) !== intval($externalAssessmentID)) {
			$this->displayMessage('The specified record cannot be found.') ;
		}
		else
		{
			$el = new \stdClass();
			$el->action = false;
			$this->render('externalAssessment.field.listStart', $el);
			$eafObj->action = false;
			$this->render('externalAssessment.field.listMember', $eafObj);
			$this->render('externalAssessment.field.listEnd', $el);
			$this->getForm(null, array('q' => '/modules/School Admin/externalAssessments_manage_edit_field_deleteProcess.php', 'gibbonExternalAssessmentFieldID' => $externalAssessmentFieldID, 'gibbonExternalAssessmentID' => $externalAssessmentID), true)
				->deleteForm();
		}
	}
}
