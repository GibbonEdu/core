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
use Gibbon\Record\schoolYear ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->addTrail('Manage School Years' , "/index.php?q=/modules/School Admin/schoolYear_manage.php");
	$header = $trail->trailEnd = $_GET["gibbonSchoolYearID"] != 'Add' ? 'Edit School Year' : 'Add School Year' ;
	$trail->render($this);
	
	$this->render('default.flash');
	

	if ($header == 'Edit School Year') $this->linkTop(array('Add' => array('q' => '/modules/School Admin/schoolYear_manage_edit.php', 'gibbonSchoolYearID' => 'Add')));
	
	$year = new schoolYear($this);

	//Check if school year specified
	$SchoolYearID=$_GET["gibbonSchoolYearID"] ;
	if (! intval($SchoolYearID) > 0 && ! $SchoolYearID == 'Add') {
		$this->displayMessage("You have not specified one or more required parameters.") ;
	}
	else {
		if (intval($SchoolYearID) > 0 ) {
			if (! ($record = $year->find($SchoolYearID))) 
			{
				$this->insertMessage($year->getError(), '', true);
			}
		}
		else	
			$record = $year->defaultRecord();
		if ($record) 
		{
			$this->h2($header);
			$form = $this->getForm(null, array('q'=>"/modules/School Admin/schoolYear_manage_editProcess.php", 'gibbonSchoolYearID' => $SchoolYearID), true);
				
			$el = $form->addElement('text', 'name', isset($record->name) ? $this->htmlPrep($record->name) : "" );
			$el->nameDisplay = 'Name';
			$el->setMaxLength(9);
			$el->setRequired();
	
			$el = $form->addElement('select', 'status', isset($record->status) ? $this->htmlPrep($record->status) : "Upcoming" );
			$el->nameDisplay = 'Status';
			$el->addOption($this->__('Past'), 'Past');
			$el->addOption($this->__('Current'), 'Current');
			$el->addOption($this->__('Upcoming'), 'Upcoming');
	
			$el = $form->addElement('text', 'sequenceNumber', isset($record->sequenceNumber) ? $this->htmlPrep($record->sequenceNumber) : "" );
			$el->nameDisplay = 'Sequence Number';
			$el->description = 'Must be unique. Controls chronological ordering.';
			$el->setMaxLength(3);
			$el->setRequired();
			$el->setNumericality();
	
			$el = $form->addElement('date', 'firstDay', isset($record->dateCorrectionOffSet) ? $record->dateCorrectionOffSet : null );
			$el->nameDisplay = 'First Day';
			$el->description = $this->session->get("i18n.dateFormat");
			$el->setMaxLength(10);
			$el->setDate('Format as: '.$this->session->get("i18n.dateFormat"));
			$el->set('formID', 'TheForm');
			$el->setRequired();
	
			$el = $form->addElement('date', 'lastDay', isset($record->lastDay) ? $record->lastDay  : null );
			$el->nameDisplay = 'Last Day';
			$el->description = $this->session->get("i18n.dateFormat");
			$el->setMaxLength(10);
			$el->set('formID', 'TheForm');
			$el->setRequired();
			
			$form->addElement('submitBtn', '', '');
	
			$form->render();
		}
	}
}
