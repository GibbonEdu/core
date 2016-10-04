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
use Gibbon\Record\yearGroup ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->addTrail('Manage Year Groups', array('q' => '/modules/School Admin/yearGroup_manage.php'));
	$header = $trail->trailEnd = isset($_GET["gibbonYearGroupID"]) && $_GET["gibbonYearGroupID"] == 'Add' ? 'Add Year Group' : 'Edit Year Group' ;
	$trail->render($this);
	
	$this->render('default.flash');
	if ($header === 'Edit Year Group') $this->linkTop(array('Add' => array('q'=>'/modules/School Admin/yearGroup_manage_edit.php', 'gibbonYearGroupID'=>'Add')));
	$this->h2($header);
	
    //Check if school year specified
    if (empty($_GET['gibbonYearGroupID'])) {
        $this->displayMessage('You have not specified one or more required parameters.');
    } else {
		$dbObj = new yearGroup($this, $_GET['gibbonYearGroupID']);

        if (! $dbObj->getSuccess()) {
            $this->displayMessage('The specified record cannot be found.');
        } else {
            //Let's go!
			$form = $this->getForm(null, array('q' => '/modules/School Admin/yearGroup_manage_editProcess.php', 'gibbonYearGroupID' => $_GET["gibbonYearGroupID"]), true);

			$el = $form->addElement('text', 'name', $this->htmlPrep($dbObj->getField("name")));
			$el->setRequired();
			$el->setMaxLength(30);
			$el->nameDisplay = 'Name' ;
			$el->description = 'Must be unique.' ;
			
			$el = $form->addElement('text', 'nameShort', $this->htmlPrep($dbObj->getField("nameShort")));
			$el->setRequired();
			$el->setMaxLength(30);
			$el->nameDisplay = 'Short Name' ;
			$el->description = 'Must be unique.' ;
			
			$el = $form->addElement('number', 'sequenceNumber', $dbObj->getField("sequenceNumber"));
			$el->setRequired();
			$el->setMaxLength(5);
			$el->nameDisplay = 'Sequence Number' ;
			$el->description = 'Must be unique. Controls chronological ordering.' ;
			
			$form->addElement('submitBtn', null);  
			$form->render();  
        }
    }
}
