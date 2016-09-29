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
	$trail->trailEnd = 'Delete Note Category';
	$trail->addTrail('Manage Students Settings', array('q'=>'/modules/School Admin/studentsSettings.php'));
	$trail->render($this);
	
	$this->render('default.flash');

    //Check if school year specified
    $studentNoteCategoryID = $_GET['gibbonStudentNoteCategoryID'];
    if ($studentNoteCategoryID == '') {
        $this->displayMessage('You have not specified one or more required parameters.');
    } else {
		$dbObj = new \Gibbon\Record\studentNoteCategory($this, $studentNoteCategoryID);
        if ($dbObj->rowCount() != 1) {
            $this->displayMessage('The specified record cannot be found.');
        } else {
            //Let's go!
            $dbObj->action = false;
			$this->render('studentSettings.listStart', $dbObj);
			$this->render('studentSettings.listMember', $dbObj);
			$this->render('studentSettings.listEnd');
			$this->getForm(null, array('q' => '/modules/School Admin/studentsSettings_noteCategory_deleteProcess.php', 'gibbonStudentNoteCategoryID'=>$studentNoteCategoryID), true)
				->deleteForm();
        }
    }
}
