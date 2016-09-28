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
use Gibbon\Record\space ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->addTrail('Manage Facilities', array('q'=>'/modules/School Admin/space_manage.php'));
	$header = $trail->trailEnd = isset($_GET["gibbonSpaceID"]) && $_GET["gibbonSpaceID"] == 'Add' ? 'Add Facility' : 'Edit Facility' ;
	$trail->render($this);
	
	$this->render('default.flash');
	if ($header === 'Edit Facility') $this->linkTop(array('Add' => array('q' => '/modules/School Admin/space_manage_edit.php', 'gibbonSpaceID' => 'Add')));
	$this->h2($header);	

    //Check if school year specified
    if (empty($_GET['gibbonSpaceID'])) {
        $this->displayMessage('You have not specified one or more required parameters.');
    } else {
		$dbObj = new space($this, $_GET['gibbonSpaceID']);

        if (! $dbObj->getSuccess()) {
            $this->displayMessage('The specified record cannot be found.');
        } else {
            //Let's go!
			$form = $this->getForm(null, array('q' => '/modules/School Admin/space_manage_editProcess.php', 'gibbonSpaceID'=>$_GET["gibbonSpaceID"]), true);

			$el = $form->addElement('text', 'name', $this->htmlPrep($dbObj->getField("name")));
			$el->setRequired();
			$el->setMaxLength(30);
			$el->nameDisplay = 'Name' ;
			$el->description = 'Must be unique.' ;

			$types = explode(',', $this->config->getSettingByScope('School Admin', 'facilityTypes'));

			$el = $form->addElement('select', 'type', $dbObj->getField("type"));
			$el->setPleaseSelect();
			$el->nameDisplay = 'Type' ;
			$el->addOption($this->__('Please select...'), 'Please select...');
			foreach($types as $w)
				$el->addOption(trim($w));

			$el = $form->addElement('select', 'gibbonPersonID1', $dbObj->getField("gibbonPersonID1"));
			$el->nameDisplay = 'User 1' ;
			$pObj = new \Gibbon\Record\person($this);
			$people = $pObj->findAllStaff();
			$el->addOption('');
			foreach($people as $person)
				$el->addOption($person->formatName(true, true), $person->getfield('gibbonPersonID'));

			$el = $form->addElement('select', 'gibbonPersonID2', $dbObj->getField("gibbonPersonID2"));
			$el->nameDisplay = 'User 2' ;
			$el->addOption('');
			foreach($people as $person)
				$el->addOption($person->formatName(true, true), $person->getfield('gibbonPersonID'));

			$el = $form->addElement('number', 'capacity', $dbObj->getField("capacity"));
			$el->nameDisplay = 'Capacity' ;
			$el->setMaxLength(5);
			$el->setInteger();

			$el = $form->addElement('yesno', 'computer', $dbObj->getField("computer"));
			$el->nameDisplay = "Teacher's Computer" ;

			$el = $form->addElement('number', 'computerStudent', $dbObj->getField("computerStudent"));
			$el->nameDisplay = 'Student Computers' ;
			$el->description = 'How many are there?';
			$el->setInteger();

			$el = $form->addElement('yesno', 'projector', $dbObj->getField("projector"));
			$el->nameDisplay = 'Projector' ;

			$el = $form->addElement('yesno', 'tv', $dbObj->getField("tv"));
			$el->nameDisplay = 'Television' ;

			$el = $form->addElement('yesno', 'dvd', $dbObj->getField("dvd"));
			$el->nameDisplay = 'DVD Player' ;

			$el = $form->addElement('yesno', 'hifi', $dbObj->getField("hifi"));
			$el->nameDisplay = 'HiFi' ;

			$el = $form->addElement('yesno', 'speakers', $dbObj->getField("speakers"));
			$el->nameDisplay = 'Speakers' ;

			$el = $form->addElement('yesno', 'iwb', $dbObj->getField("iwb"));
			$el->nameDisplay = 'Interactive White Board' ;

			$el = $form->addElement('phone', 'phoneInternal', $this->htmlPrep($dbObj->getField("phoneInternal")));
			$el->nameDisplay = 'Extension' ;
			$el->description = "Room's internal phone number." ;
			$el->setMaxLength(5);

			$el = $form->addElement('phone', 'phoneExternal', $this->htmlPrep($dbObj->getField("phoneExternal")));
			$el->nameDisplay = 'Phone Number' ;
			$el->description = "Room's external phone number." ;
			$el->setMaxLength(20);

			$el = $form->addElement('textArea', 'comment', $this->htmlPrep($dbObj->getField("comment")));
			$el->nameDisplay = 'Comment' ;
			$el->rows = 5;

			$form->addElement('submitBtn', null);
			$form->render();
        }
    }
}
