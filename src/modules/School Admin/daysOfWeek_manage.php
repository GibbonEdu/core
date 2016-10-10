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

use Gibbon\core\view;
use Gibbon\Record\daysOfWeek ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'Manage Days of the Week';
	$trail->render($this);
	
	$this->render('default.flash');

	$dowObj = new daysOfWeek($this);
	$dowRows = $dowObj->findAllDays();

    if (count($dowRows) != 7) {
        $this->displayMessage('There is a problem with your database information for school days.');
    } else {
		$this->h2('Manage Days of the Week');
		$form = $this->getForm(null, array('q'=>"/modules/School Admin/daysOfWeek_manageProcess.php"), true);
		foreach($dowRows as $dowObj)
		{
			$form->addElement('h3', null, array('%1$s (%2$s)', array($dowObj->getField('name'), $dowObj->getField('nameShort'))));
			$form->addElement('hidden', $dowObj->getField('name')."[sequenceNumber]", $dowObj->getField('sequenceNumber'));
			
			$el = $form->addElement('yesno', $dowObj->getField('name')."[schoolDay]", $dowObj->getField('schoolDay'));
			$el->nameDisplay = 'School Day';
			$el->setRequired();
	
			$el = $form->addElement('time', $dowObj->getField('name')."[schoolOpen]", $dowObj->getField('schoolOpen'));
			$el->nameDisplay = 'School Opens';
			$el->validateOff();
			$el->row->class = 'schoolDay'.$dowObj->getField('nameShort');
			if ($dowObj->getField('schoolDay') == 'N')  
				$el->row->style = ! empty($el->row->style) ? $el->row->style.' display: none; ' : 'display: none; ' ;
	
			$el = $form->addElement('time', $dowObj->getField('name')."[schoolStart]", $dowObj->getField('schoolStart'));
			$el->nameDisplay = 'School Starts';
			$el->validateOff();
			$el->row->class = 'schoolDay'.$dowObj->getField('nameShort');
			if ($dowObj->getField('schoolDay') == 'N')  
				$el->row->style = ! empty($el->row->style) ? $el->row->style.' display: none; ' : 'display: none; ' ;
	
			$el = $form->addElement('time', $dowObj->getField('name')."[schoolEnd]", $dowObj->getField('schoolEnd'));
			$el->nameDisplay = 'School Ends';
			$el->validateOff();
			$el->row->class = 'schoolDay'.$dowObj->getField('nameShort');
			if ($dowObj->getField('schoolDay') == 'N')  
				$el->row->style = ! empty($el->row->style) ? $el->row->style.' display: none; ' : 'display: none; ' ;
	
			$el = $form->addElement('time', $dowObj->getField('name')."[schoolClose]", $dowObj->getField('schoolClose'));
			$el->nameDisplay = 'School Closes';
			$el->validateOff();
			$el->row->class = 'schoolDay'.$dowObj->getField('nameShort');
			if ($dowObj->getField('schoolDay') == 'N')  
				$el->row->style = ! empty($el->row->style) ? $el->row->style.' display: none; ' : 'display: none; ' ;

			$this->render('daysOfWeek.script', $dowObj);
		}
		$form->addElement('submitBtn', null, 'Save All');
		$form->render();
    }
}