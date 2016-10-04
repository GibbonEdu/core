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
use Gibbon\Record\INDescriptor ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->addTrail('Manage Individual Needs Descriptors', array('q'=>'/modules/School Admin/inDescriptors_manage.php'));
	$header = $trail->trailEnd = 'Edit Individual Needs Descriptor';
    $INDescriptorID = $_GET['gibbonINDescriptorID'];
	if ($INDescriptorID == 'Add')
		$header = $trail->trailEnd = 'Add Individual Needs Descriptor';
	$trail->render($this);
	
	$this->render('default.flash');

	$this->linkTop(array('Add'=>array('q'=>'/modules/School Admin/inDescriptors_manage_edit.php', 'gibbonINDescriptorID'=>'Add')));
	$this->h2($header);

    if (empty($INDescriptorID)) {
        $this->displayMessage('You have not specified one or more required parameters.');
    } else {
		$dbObj = new INDescriptor($this, $INDescriptorID);
		
        if (!$dbObj->getSuccess()) {
            $this->displayMessage('The specified record cannot be found.');
        } else {
            //Let's go!
            
			
			$form = $this->getForm($this->session->get("absolutePath") . "/modules/School Admin/inDescriptors_manage_editProcess.php", array('gibbonINDescriptorID'=>$INDescriptorID), true);

			$el = $form->addElement('text', 'name', $dbObj->getField('name'));
			$el->setMaxLength(50);
			$el->setRequired();
			$el->description = 'Must be unique.';
			$el->nameDisplay = 'Name';

			$el = $form->addElement('text', 'nameShort', $dbObj->getField('nameShort'));
			$el->setMaxLength(5);
			$el->setRequired();
			$el->description = 'Must be unique.';
			$el->nameDisplay = 'Short Name';

			$el = $form->addElement('text', 'sequenceNumber', $dbObj->getField('sequenceNumber'));
			$el->setMaxLength(5);
			$el->setRequired();
			$el->description = 'Must be unique.';
			$el->nameDisplay = 'Sequence Number';

			$el = $form->addElement('textArea', 'description', $dbObj->getField('description'));
			$el->rows = 5;
			$el->nameDisplay = 'Description';
			
			$form->addElement('submitBtn', null);
			$form->render();
        }
    }
}
