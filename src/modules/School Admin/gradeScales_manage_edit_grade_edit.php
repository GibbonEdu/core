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
use Gibbon\Record\scale ;

if (! $this instanceof view ) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$header = $trail->trailEnd = isset($_GET['gibbonScaleGradeID']) && $_GET['gibbonScaleGradeID'] == 'Add' ? 'Add Grade' : 'Edit Grade' ;
	$trail->addTrail('Manage Grade Scales', array('q' => '/modules/School Admin/gradeScales_manage.php'));
	$trail->addTrail('Edit Grade Scale', array('q' => '/modules/School Admin/gradeScales_manage_edit.php', 'gibbonScaleID' => $_GET['gibbonScaleID']));
	$trail->render($this);
	
	$this->render('default.flash');

	if ($header != 'Add Grade')
		$this->linkTop(array('add' => array('q' => '/modules/School Admin/gradeScales_manage_edit_grade_edit.php', 'gibbonScaleID' => $_GET['gibbonScaleID'], 'gibbonScaleGradeID' => 'Add')));

	$this->h2($header);
	

	//Check if school year specified
	$scaleGradeID = $_GET["gibbonScaleGradeID"] ;
	$scaleID = $_GET["gibbonScaleID"] ;
	if (empty($scaleGradeID) || empty($scaleID)) {
		$this->displayMessage("You have not specified one or more required parameters.") ;
	}
	else 
	{
		$sObj = new scale($this, $scaleID);
		$sgObj = $sObj->getGrade($scaleGradeID);

		if (! $sObj->getSuccess())  {
			$this->displayMessage("The specified record cannot be found.") ;
		}
		else {
			//Let's go!
			
			$form = $this->getForm(GIBBON_ROOT . 'modules/School Admin/gradeScales_manage_edit_grade_editProcess.php', array('gibbonScaleGradeID' => $scaleGradeID, 'gibbonScaleID' => $scaleID), true);
			
			$el = $form->addElement('text', 'name', $this->__($sObj->getField('name')));
			$el->nameDisplay = 'Grade Scale';
			$el->description = 'This value cannot be changed.';
			$el->setReadOnly();
			 
			$el = $form->addElement('text', 'value', ! empty($sgObj->getField('value')) ? $this->__($sgObj->getField('value')) : '');
			$el->nameDisplay = 'Value';
			$el->description = 'Must be unique for this grade scale.';
			$el->setMaxLength(10);
			$el->setRequired();
			
			$el = $form->addElement('text', 'descriptor', ! empty($sgObj->getField('descriptor')) ? $this->__($sgObj->getField('descriptor')) : '');
			$el->nameDisplay = 'Descriptor';
			$el->setMaxLength(50);
			$el->setRequired();
			
			$el = $form->addElement('text', 'sequenceNumber', ! empty($sgObj->getField('sequenceNumber')) ? $sgObj->getField('sequenceNumber') : '');
			$el->nameDisplay = 'Sequence Number';
			$el->description = 'Must be unique for this grade scale.';
			$el->setMaxLength(5);
			$el->setRequired();
			
			$el = $form->addElement('yesno', 'isDefault', $sgObj->getField('isDefault'));
			$el->nameDisplay = 'Is Default?';
			$el->description = 'Preselects this option when using this grade scale in appropriate contexts.';
			
			$form->addElement('submitBtn', null);
			$form->addElement('hidden', 'gibbonScaleID', $scaleID);
			
			$form->render();
		}
	}
}
