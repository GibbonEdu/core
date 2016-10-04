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

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$header = $trail->trailEnd = 'Edit Note Category';
    $studentNoteCategoryID = $_GET['gibbonStudentNoteCategoryID'];
	if ($studentNoteCategoryID == 'Add')
		$header = $trail->trailEnd = 'Add Note Category';
	$trail->addTrail('Manage Students Settings', array('q'=>'/modules/School Admin/studentsSettings.php'));
	$trail->render($this);
	
	$this->render('default.flash');

	if ($header == 'Edit Note Category') $this->linkTop(array('Add'=> array('q' => '/modules/School Admin/studentsSettings_noteCategory_edit.php', 'gibbonStudentNoteCategoryID'=>'Add')));
	$this->h2($header);
    //Check if school year specified
    if (empty($studentNoteCategoryID)) {
        $this->displayMessage('You have not specified one or more required parameters.');
    } else {
		$dbObj = new \Gibbon\Record\studentNoteCategory($this, $studentNoteCategoryID);
		
        if (! $dbObj->getSuccess()) {
            $this->displayMessage('The specified record cannot be found.');
        } else {
            //Let's go!
            $row = $dbObj->returnRecord();
			
			$form = $this->getForm(null, array('q' => '/modules/School Admin/studentsSettings_noteCategory_editProcess.php', 'gibbonStudentNoteCategoryID'=>$studentNoteCategoryID), true);
			$form->addElement('h3', null, 'Student Note Category Management');

			$el = $form->addElement('text', 'name', $row->name);
			$el->setRequired ();
			$el->setMaxLength ( 30);
			$el->nameDisplay = 'Name';
			$el->description = 'Must be unique.';

			$el = $form->addElement('yesno', 'active', $row->active);
			$el->nameDisplay = 'Active';

			$el = $form->addElement('textArea', 'template', $row->template);
			$el->nameDisplay = 'Template';
			$el->description = 'HTML code to be inserted into blank note.';

			$form->addElement('submitBtn', null);
			$form->render();
        }
    }
}
