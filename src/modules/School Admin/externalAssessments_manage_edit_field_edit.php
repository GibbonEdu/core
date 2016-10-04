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
use Gibbon\Record\externalAssessment ;
use Gibbon\Record\externalAssessmentField ;
use Gibbon\Record\scale ;
use Gibbon\Record\yearGroup ;

if (! $this instanceof view ) die();

if ($this->getSecurity()->isActionAccessible()) {
	$trail = $this->initiateTrail();
	$header = $trail->trailEnd = ! empty($_GET['gibbonExternalAssessmentFieldID']) && $_GET['gibbonExternalAssessmentFieldID'] === 'Add' ? 'Add Grade' : 'Edit Grade' ;
	$trail->addTrail('Manage External Assessments', '/index.php?q=/modules/School Admin/externalAssessments_manage.php');
	$trail->addTrail('Edit External Assessment', '/index.php?q=/modules/School Admin/externalAssessments_manage_edit.php&gibbonExternalAssessmentID=' . $_GET['gibbonExternalAssessmentID']);
	$trail->render($this);

	$this->render('default.flash');
	$externalAssessmentFieldID = $_GET['gibbonExternalAssessmentFieldID'] ;
	$externalAssessmentID = $_GET['gibbonExternalAssessmentID'] ;
	
	$this->linkTop(array('add' => array('q' => '/modules/School Admin/externalAssessments_manage_edit_field_edit.php', 'gibbonExternalAssessmentID' => $externalAssessmentID, 'gibbonExternalAssessmentFieldID' => 'Add')));
	$this->h2($header);

	//Check if school year specified
	if (empty($externalAssessmentFieldID) || empty($externalAssessmentID)) {
		$this->displayMessage("You have not specified one or more required parameters.") ;
	}
	else 
	{
		$eaObj = new externalAssessment($this, $externalAssessmentID);
		$eafObj = new externalAssessmentField($this, $externalAssessmentFieldID);
		$found = false;
		if (! ($externalAssessmentFieldID === 'Add' || intval($eafObj->getField('gibbonExternalAssessmentID')) === intval($externalAssessmentID))) {
			$this->displayMessage("The specified record cannot be found.") ;
		}
		else
		{
			//Let's go!
			
			$form = $this->getForm(GIBBON_ROOT . 'modules/School Admin/externalAssessments_manage_edit_field_editProcess.php', array('gibbonExternalAssessmentFieldID' => $externalAssessmentFieldID, 'gibbonExternalAssessmentID' => $externalAssessmentID), true);

			$el = $form->addElement('text', 'assessmentName', $this->__($eaObj->getField('name')));
			$el->nameDisplay = 'External Assessment' ;
			$el->description = 'This value cannot be changed.' ;
			$el->setReadOnly();
			
			$el = $form->addElement('text', 'name', $this->__($eafObj->getField('name')));
			$el->nameDisplay = 'Name' ;
			$el->setMaxLength(50);
			$el->setRequired();
			
			$el = $form->addElement('text', 'category', $this->__($eafObj->getField('category')));
			$el->nameDisplay = 'Category' ;
			$el->setMaxLength(50);
			$el->setRequired();
			
			$el = $form->addElement('text', 'order', $eafObj->getField('order'));
			$el->nameDisplay = 'Order' ;
			$el->setMaxLength(4);
			$el->setRequired();
			
			$sObj = new scale($this);
			$sList = $sObj->findAllBy(array('active'=>'Y'), array('name'=>'ASC')); 

			$el = $form->addElement('select', 'gibbonScaleID', $eafObj->getField('gibbonScaleID'));
			$el->setPleaseSelect();
			$el->nameDisplay = 'Grade Scale';
			$el->description = 'Grade scale used to control values that can be assigned.' ;
			$el->addOption($this->__('Please select...'), 'Please select...');
			foreach($sList as $scale)
				$el->addOption($this->htmlPrep($this->__( $scale->name)), $scale->gibbonScaleID);
			
			$ygObj = new yearGroup($this);
			$yearGroups = $ygObj->getYearGroups() ;
			$selectedYears = explode(",", $eafObj->getField('gibbonYearGroupIDList')) ;

			
			$el = $form->addElement('optGroup', null);
			$el->nameDisplay = 'Year Groups' ;
			$el->description = 'Year groups to which this field is relevant.' ;
			$el->emptyMessage = 'No year groups available.' ;
			$this->addScript( '
<script type="text/javascript">
	$(function () {
		$(".checkall").click(function () {
			$(this).parents("fieldset:eq(0)").find(":checkbox").attr("checked", this.checked);
		});
	});
</script>
');
			$el->optionType = 'checkbox';
			$op = $el->addOption('checkAll', '', $this->__( "All") .  " / " . $this->__( "None"));
			$op->class = 'checkall';
			for ($i=0; $i<count($yearGroups); $i=$i+2) {
				$checked = false ;
				foreach ($selectedYears as $selectedYear) 
					if ($selectedYear == $yearGroups[$i]) 
						$checked = true;
				$op = $el->addOption('gibbonYearGroupID[' . (($i)/2).']', $yearGroups[$i], $this->__($yearGroups[($i+1)]), $checked);
			}

			$form->addElement('hidden', 'count', count($yearGroups)/2);
			$form->addElement('hidden', 'gibbonExternalAssessmentID', $externalAssessmentID);
			$form->addElement('submitBtn', null);
			
			$form->render();
		}
	}
}
