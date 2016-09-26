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

if (! $this instanceof \Gibbon\core\view) die();

use Gibbon\core\trans ;
use Gibbon\core\helper ;
use Gibbon\Record\externalAssessment ;
use Gibbon\Record\externalAssessmentField ;

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$header = $trail->trailEnd = ! empty($_GET['gibbonExternalAssessmentID']) && $_GET['gibbonExternalAssessmentID'] !== 'Add' ? 'Edit External Assessments' :  'Add External Assessments';
	$trail->addTrail('Manage External Assessments', array('q' => '/modules/School Admin/externalAssessments_manage.php'));
	$trail->render($this);
	
	$this->render('default.flash');
	
	if ($header !== 'Add External Assessments') $this->linkTop(array('add' => array('q' => '/modules/School Admin/externalAssessments_manage_edit.php', 'gibbonExternalAssessmentID'=>'Add')));	
	$this->h2($header);	

	
	//Check if school year specified
	$externalAssessmentID = $_GET["gibbonExternalAssessmentID"] ;
	if (empty($externalAssessmentID)) {
		$this->displayMessage("You have not specified one or more required parameters.") ;
	}
	else {
		$eaObj = new externalAssessment($this, $externalAssessmentID);
		
		if (! $eaObj->getSuccess()) {
			$this->displayMessage("The specified record cannot be found.") ;
		}
		else {
			//Let's go!
			
			$form = $this->getForm(GIBBON_ROOT . 'modules/School Admin/externalAssessments_manage_editProcess.php', array('gibbonExternalAssessmentID' => $externalAssessmentID), true);
			
			$el = $form->addElement('text', 'name', $this->__($eaObj->getField('name')));
			$el->nameDisplay = 'Name';
			$el->setRequired();
			$el->description = 'Must be unique.';
			$el->setMaxLength(50);

			$el = $form->addElement('text', 'nameShort', $this->__($eaObj->getField('nameShort')));
			$el->nameDisplay = 'Short Name';
			$el->setRequired();
			$el->setMaxLength(10);

			$el = $form->addElement('textArea', 'description', $this->__($eaObj->getField('description')));
			$el->nameDisplay = 'Description';
			$el->description = 'Brief description of how scale is used.';
			$el->setRequired();
			$el->rows = 4;
			$el->setMaxLength(250);

			$el = $form->addElement('yesno', 'active', $eaObj->getField('active'));
			$el->nameDisplay = 'Active';

			$el = $form->addElement('yesno', 'allowFileUpload', $eaObj->getField('allowFileUpload'));
			$el->nameDisplay = 'Allow File Upload';
			$el->description = 'Should the student record include the option of a file upload?';

			$el = $form->addElement('url', 'website', $eaObj->getField('website'));
			$el->nameDisplay = 'Web Site';
			$el->description = 'Assessment Web Site';
			$el->setMaxLength(250);

			$form->addElement('hidden', 'gibbonExternalAssessmentID', $_GET["gibbonExternalAssessmentID"]);
			$form->addElement('submitBtn', null);
			$form->render();
			
			if ($header === 'Edit External Assessments') {
			
				$this->linkTop(array('add' => array('q' => '/modules/School Admin/externalAssessments_manage_edit_field_edit.php', 'gibbonExternalAssessmentID' => $externalAssessmentID, 'gibbonExternalAssessmentFieldID' => 'Add')));
				$this->h3("Edit Fields") ;
				$this->displayMessage('Drag and drop table rows to sort position. Rows are still sorted by Category then Order. Dragging a field outside it category will result in the field being placed at the first or last in the category.', 'info');
				$eafList = $eaObj->getFields();
	
				
				if (count($eafList) < 1) {
					$this->displayMessage("There are no records to display.") ;
				}
				else {
					$this->render('externalAssessment.field.listStart');
					foreach($eafList as $field)
						$this->render('externalAssessment.field.listMember', $field);
					$this->render('externalAssessment.field.listEnd', $field);
				}
			}
		}
	}
}