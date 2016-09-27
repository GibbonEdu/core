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
/**
 * The file will arrange a form to add/edit a department details, plus it will
 * add and remove staff from the department.  Staff in the department must be removed before adding to a new role.
 * This can be done in a single transaction as removals are processed before additions.
 * Duplicate additions are ignored.
 */
namespace Module\School_Admin ;

use Gibbon\Record\department ;
use Gibbon\Record\departmentStaff ;
use Gibbon\Record\person ;
use Gibbon\core\view ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->addTrail('Manage Departments', "/index.php?q=/modules/School Admin/department_manage.php");
	$header = $trail->trailEnd = isset($_GET["gibbonDepartmentID"]) && $_GET["gibbonDepartmentID"] == 'Add' ? 'Add Department' : 'Edit Department' ;
	$trail->render($this);
	
	$this->render('default.flash');
	
	//Check if school year specified
	$departmentID = $_GET["gibbonDepartmentID"];
	if (empty($departmentID)) {
		$this->displayMessage("You have not specified one or more required parameters.") ;
	}
	else {
		$dObj = new department($this, $departmentID);

		if ($dObj->rowCount() != 1 && $departmentID !== 'Add') {
			$this->displayMessage("The selected record does not exist, or you do not have access to it.") ;
		}
		else {
			//Let's go!
			$this->h2($header);
			$form = $this->getForm(null, array('q'=>'/modules/School Admin/department_manage_editProcess.php', 'gibbonDepartmentID' => $departmentID), true, 'TheForm', true);

			$form->addElement('h3', null, 'General Information');
			
			$el = $form->addElement('select', 'type', $dObj->getField('type'));
			if ($departmentID !== 'Add')
				$el->setDisabled();
			$el->nameDisplay = 'Type';
			$el->description = 'This value cannot be changed.';
			foreach($this->pdo->getEnum('gibbonDepartment', 'type') as $value)
				$el->addOption($this->__($value), $value);

			$el = $form->addElement('text', 'name', $this->htmlPrep($dObj->getField('name')));
			$el->setRequired();
			$el->setMaxLength(40);
			$el->nameDisplay = 'Name';

			$el = $form->addElement('text', 'nameShort', $this->htmlPrep($dObj->getField('nameShort')));
			$el->setRequired();
			$el->setMaxLength(4);
			$el->nameDisplay = 'Short Name';

			$el = $form->addElement('text', 'subjectListing', $this->htmlPrep($dObj->getField('subjectListing')));
			$el->setMaxLength(255);
			$el->nameDisplay = 'Subject Listing';

			$el = $form->addElement('editor', 'blurb', $dObj->getField('blurb'));
			$el->nameDisplay = 'Blurb';

			$el = $form->addElement('photo', 'file');
			$el->nameDisplay = 'Logo';
			$x = 'Displayed at 125px by 125px.%1$sAccepts images up to 125px by 125px.%2$s';
			if (! empty($dObj->getField('logo')))
			{
				$x .= 'Will overwrite existing attachment.';
				$y = " <img style='float: left; width: 75px' src='".GIBBON_URL.$dObj->getField('logo')."'/><a target='_blank' href='".GIBBON_URL.$dObj->getField('logo')."'>".$dObj->getField('logo')."</a>";
				$y .= $this->returnLinkImage('delete', "style='margin-bottom: -8px' id='logo_delete'")."</a><br/><br/>";
				$el->deletePhoto = array('Current attachment:%1$s', array($y));
			}
			$el->description = array($x, array('<br />', '<br />'));
		
			$el = $form->addElement('hidden', 'logo', $dObj->getField('logo'));
			
			$form->addElement('h3', null, 'Current Staff');

			$el = new \stdClass();
			$dsObj = new departmentStaff($this);
			$el->staff = $dsObj->getDepartmentStaff($departmentID);
			$el->departmentID = $departmentID ;
			$el->form = $form ;
			
			$form->addElement('raw', null, $this->renderReturn('department.currentStaff', $el));

			$form->addElement('h3', null, 'New Staff');
			
			$el = $form->addElement('select', 'staff[]');
			$el->nameDisplay = 'Staff';
			$el->description = 'Use Control, Command and/or Shift to select multiple.';
			$el->setMultiple();
			$el->element->style = 'min-height: 100px; ';
			$pObj = new person($this);
			foreach($pObj->findAllStaff() as $person)
				$el->addOption($person->formatName(true, true), $person->getField('gibbonPersonID'));
			
			$el = $form->addElement('select', 'role');
			$w = $this->config->getStaff();
			$type = in_array($dObj->getField('type'), array("Learning Area", "Administration")) ? $dObj->getField('type') : 'Unknown' ;
			$roles = $w['role'][$type];
			foreach($roles as $q=>$w) 
				$el->addOption($this->__($w), $q);
			$el->row->id = 'roleLARow';
			
			$form->addElement('submitBtn', null);
			
			$form->render();
		}
	}
}
