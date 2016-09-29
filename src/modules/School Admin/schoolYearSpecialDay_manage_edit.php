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
use Gibbon\Record\schoolYearSpecialDay ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$header = $trail->trailEnd = 'Edit Special Day';
	$trail->addTrail('Manage Special Days', array('q'=>'/modules/School Admin/schoolYearSpecialDay_manage.php','gibbonSchoolYearID'=>$_GET['gibbonSchoolYearID']));
	$header = $trail->trailEnd =  $_GET['gibbonSchoolYearSpecialDayID'] == 'Add' ? 'Add Special Day' : 'Edit Special Day' ;
	$trail->render($this);

	$this->render('default.flash');

    //Check if school year specified
    $schoolYearSpecialDayID = $_GET['gibbonSchoolYearSpecialDayID'];
    if ($schoolYearSpecialDayID == '') {
        $this->displayMessage('You have not specified one or more required parameters.');
    } else {
		$specDayObj = new schoolYearSpecialDay($this, $schoolYearSpecialDayID);
        if (! $specDayObj->getSuccess()) {
            $this->displayMessage('The specified record cannot be found.');
        } else {
            //Let's go!
			$this->h2($header);
			
			$value = $specDayObj->getField('date');
			if (empty($value))
				$value = date('Y-m-d', $_GET['dateStamp']);
				
			$form = $this->getForm(null, array('q'=>'/modules/School Admin/schoolYearSpecialDay_manage_editProcess.php', 'gibbonSchoolYearSpecialDayID' => $schoolYearSpecialDayID, 
												'gibbonSchoolYearID' => $_GET['gibbonSchoolYearID'], 'dateStamp'=>strtotime($value)), true);
  			
			if (isset($_GET['gibbonSchoolYearTermID']))
				$form->addElement('hidden', 'gibbonSchoolYearTermID', $_GET['gibbonSchoolYearTermID']);			
			$form->addElement('hidden', 'gibbonSchoolYearID', $_GET['gibbonSchoolYearID']);			
			
			$el = $form->addElement('date', "date", $value);
			$el->nameDisplay = 'Date';
			$el->description = 'This value cannot be changed.';
			$el->setReadOnly();

			$el = $form->addElement('select', "type", $specDayObj->getField('type'));
			$el->setPleaseSelect();
			$el->nameDisplay = 'Type';
			$el->addOption($this->__('Please select...'), 'Please select...');
			foreach($this->pdo->getEnum('gibbonSchoolYearSpecialDay', 'type') as $value)
				$el->addOption($this->__($value), $value);

			$el = $form->addElement('text', "name", $specDayObj->getField('name'));
			$el->setRequired();
			$el->nameDisplay = 'Name';
			$el->maxLength = 20;

			$el = $form->addElement('text', "description", $specDayObj->getField('description'));
			$el->validateOff();
			$el->nameDisplay = 'Description';
			$el->setMaxLength = 255;

			$el = $form->addElement('time', "schoolOpen", $specDayObj->getField('schoolOpen'));
			$el->validateOff();
			$el->nameDisplay = 'School Opens';
			$el->row->class = 'schoolTimeRow';
			if ($specDayObj->getField('type') == 'School Closure') 
				$el->row->style = !empty($el->row->style) ? $el->row->style.' display: none; ': 'display: none; ' ;

			$el = $form->addElement('time', "schoolStart", $specDayObj->getField('schoolStart'));
			$el->validateOff();
			$el->nameDisplay = 'School Starts';
			$el->row->class = 'schoolTimeRow';
			if ($specDayObj->getField('type') == 'School Closure') 
				$el->row->style = !empty($el->row->style) ? $el->row->style.' display: none; ': 'display: none; ' ;

			$el = $form->addElement('time', "schoolEnd", $specDayObj->getField('schoolEnd'));
			$el->validateOff();
			$el->nameDisplay = 'School Ends';
			$el->row->class = 'schoolTimeRow';
			if ($specDayObj->getField('type') == 'School Closure') 
				$el->row->style = !empty($el->row->style) ? $el->row->style.' display: none; ': 'display: none; ' ;

			$el = $form->addElement('time', "schoolClose", $specDayObj->getField('schoolClose'));
			$el->validateOff();
			$el->nameDisplay = 'School Closes';
			$el->row->class = 'schoolTimeRow';
			if ($specDayObj->getField('type') == 'School Closure') 
				$el->row->style = !empty($el->row->style) ? $el->row->style.' display: none; ': 'display: none; ' ;
			
			$form->addElement('submitBtn', null);

			$scriptDisplayMode = $this->session->get('theme.settings.script.display');

			$this->addScript('
<script type="text/javascript">
	$(document).ready(function(){
		 $("#_type").click(function(){
			if ($("#_type option:selected").val()=="School Closure" ) {
				$(".schoolTimeRow").css("display","none");
			} else {
				$(".schoolTimeRow").slideDown("fast", $(".schoolTimeRow").css("display","'.$scriptDisplayMode.'")); 
			}
		 });
	});
</script>
			');

            $form->render();
        } 
    } 
}
