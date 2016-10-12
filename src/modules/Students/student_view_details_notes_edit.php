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

namespace Module\Students ;

use Gibbon\core\view ;
use Gibbon\Person\student ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
    $allStudents = isset($_GET['allStudents']) ? $_GET['allStudents'] : '';

    $enableStudentNotes = $this->config->getSettingByScope('Students', 'enableStudentNotes');
    if ($enableStudentNotes != 'Y') {
        $this->displayMessage('You do not have access to this action.');
    } else {
        $personID = $_GET['gibbonPersonID'];
        $subpage = $_GET['subpage'];
		$search = isset($_GET['search']) ? $_GET['search'] : '';
		$allStudents = isset($_GET['allStudents']) ? $_GET['allStudents'] : '';
		$category = isset($_GET['category']) ? $_GET['category'] : '';
        if (empty($personID) || empty($subpage)) {
            $this->displayMessage('You have not specified one or more required parameters.');
        } else {
			$student = new student($this, $personID);

            if (! $student->getSuccess() || $student->rowCount() !== 1) {
               $this->displayMessage('The selected record does not exist, or you do not have access to it.');
            } else {

                //Proceed!
				$trail = $this->initiateTrail();
				$header = $trail->trailEnd = $_GET['gibbonStudentNoteID'] === 'Add' ? 'Add Student Note' : 'Edit Student Note';
				$trail->addTrail('View Student Profiles', array('q'=>'/modules/Students/student_view.php', 'search'=>$search, 'allStudents'=>$allStudents));
				$trail->addTrail($student->formatName(), array('q'=>'/modules/Students/student_view_details.php', 'search'=>$search, 'allStudents'=>$allStudents, 'gibbonPersonID'=>$personID));
				$trail->render($this);

				$this->render('default.flash');
				
                $noteID = $_GET['gibbonStudentNoteID'];
                if (empty($noteID)) {
                    $this->displayMessage('You have not specified one or more required parameters.');
                } else {
					$note = $student->getNote($noteID);

                    if (! $student->validNote) {
                        $this->displayMessage('The specified record cannot be found.');
                    } else {
                        //Let's go!

                        if (! empty($search)) 
							$this->linkTop('Back to Search Results', array('q'=>'modules/Students/student_view_details.php', 'gibbonPersonID'=>$personID,
								'search'=>$search, 'subpage'=>$subpage,'category'=>$category, 'allStudents'=>$allStudents));
								
						$this->h2($header);
						$form = $this->getForm(null, array('q'=>'/modules/Students/student_view_details_notes_editProcess.php', 'gibbonPersonID'=>$personID, 'search'=>$search,
							'subpage'=>$subpage, 'gibbonStudentNoteID'=>$noteID, 'category'=>$category, 'allStudents'=>$allStudents), true);
						
						$el = $form->addElement('text', 'title', $note->getField('title'));	
						$el->setMaxLength(100);
						$el->setRequired();
						$el->nameDisplay = 'Title';
						
						$categories = $student->getNoteCategories();
						
						$el = $form->addElement('select', 'gibbonStudentNoteCategoryID', $note->getField('gibbonStudentNoteCategoryID'));
						$el->nameDisplay = 'Category';
						$el->setPleaseSelect();
						foreach($categories as $id=>$name)
							$el->addOption($name, $id);
						
						$el = $form->addElement('editor', 'note', $note->getField('note'));
						$el->nameDisplay = 'Note';
						$el->setRequired();
						
						$form->addElement('submitBtn', null);
						
						$form->render();
                    }
                }
            }
        }
    }
}
