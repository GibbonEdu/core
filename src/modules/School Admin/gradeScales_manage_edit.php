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

use Gibbon\Record\scale ;
use Gibbon\Record\scaleGrade ;
use Gibbon\core\view ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$header = $trail->trailEnd = isset($_GET['gibbonScaleID']) && $_GET['gibbonScaleID'] == 'Add' ? 'Add Grade Scale' : 'Edit Grade Scale' ;
	$trail->addTrail('Manage Grade Scales', array('q' => '/modules/School Admin/gradeScales_manage.php'));
	$trail->render($this);
	
	$this->render('default.flash');

	$this->h2($header);
	
	//Check if school year specified
	$scaleID = $_GET["gibbonScaleID"] ;
	if (empty($scaleID)) {
		$this->displayMessage("You have not specified one or more required parameters.") ;
	}
	else {
		$sObj = new scale($this, $scaleID);
		$data=array("gibbonScaleID"=>$scaleID); 

		if ($sObj->rowCount() != 1 && $scaleID !== 'Add') {
			$this->displayMessage("The specified record cannot be found.") ;
		}
		else {
			//Let's go!
			
			$form = $this->getForm(null, array('q'=> '/modules/School Admin/gradeScales_manage_editProcess.php', 'gibbonScaleID' => $scaleID), true);
			
			$el = $form->addElement('text', 'name', ! empty($sObj->getField("name")) ? $this->htmlPrep($this->__($sObj->getField("name"))) : '');
			$el->nameDisplay = 'Name';
			$el->description = 'Must be unique for this school year.' ;
			$el->setMaxLength(40);
			$el->setRequired();
			
			$el = $form->addElement('text', 'nameShort', ! empty($sObj->getField("nameShort")) ? $this->htmlPrep($this->__($sObj->getField("nameShort"))) : '');
			$el->nameDisplay = 'Short Name';
			$el->setMaxLength(4);
			$el->setRequired();
			
			$el = $form->addElement('text', 'usage', ! empty($sObj->getField("usage")) ? $this->htmlPrep($this->__($sObj->getField("usage"))) : '');
			$el->nameDisplay = 'Usage';
			$el->description = 'Brief description of how scale is used.';
			$el->setMaxLength(50);
			$el->setRequired();
			
			$el = $form->addElement('yesno', 'active', $sObj->getField("active"));
			$el->nameDisplay = 'Active';
			
			$el = $form->addElement('yesno', 'numeric', $sObj->getField("numeric"));
			$el->nameDisplay = 'Numeric?';
			$el->description = 'Does this scale use only numeric grades? Note, grade "Incomplete" is exempt.';
			
			$scaleGrades = $sObj->getGrades();
			
			$el = $form->addElement('select', 'lowestAcceptable', $sObj->getField("lowestAcceptable"));
			$el->nameDisplay = 'Lowest Acceptable';
			$el->description = 'This is the lowest grade a student can get without being unsatisfactory.';
			foreach($scaleGrades as $scaleGrade)
				$el->addOption($scaleGrade->getField('value'), $scaleGrade->getField('sequenceNumber'));
				
			$form->addElement('submitBtn', null);
			$form->render();
					
			$this->linkTop(array('add' => array('q'=>'/modules/School Admin/gradeScales_manage_edit_grade_edit.php', 'gibbonScaleID' => $scaleID, 'gibbonScaleGradeID' => 'Add')));

			$this->h3("Edit Grades") ;
			$this->displayMessage('Drag and drop table rows to sort position. Rows are still sorted by Category then Order. Dragging a field outside it category will result in the field being placed at the first or last in the category.', 'info');

			if (count($scaleGrades) < 1) {
				$this->displayMessage("There are no records to display.") ;
			}
			else {
				$this->render('scale.grade.listStart');	
				foreach($scaleGrades as $scaleGrade)
					$this->render('scale.grade.listMember', $scaleGrade);	
				$this->render('scale.grade.listEnd', $scaleGrade);
			} 
		} 
	}
}
