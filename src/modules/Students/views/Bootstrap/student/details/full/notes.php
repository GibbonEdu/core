<?php
use Gibbon\trans ;

$enableStudentNotes = $this->config->getSettingByScope('Students', 'enableStudentNotes');

if ($enableStudentNotes != 'Y') {
	$this->displayMessage('You do not have access to this action.');
} else {
	$this->h2('Student Notes');
	if (! $this->getSecurity()->isActionAccessible('/modules/Students/student_view_details_notes_edit.php')) {
		$this->displayMessage('Your request failed because you do not have access to this action.');
	} else {
		$this->render('default.flash');

		$this->displayMessage(array('Student Notes provide a way to store information on students which does not fit elsewhere in the system, or which you want to be able to see quickly in one place.', array(' <strong>'.trans::__('Please remember that notes are visible to other users who have access to full student profiles (this should not generally include parents).').'</strong>')), 'info');

		$categories = false;
		$category = isset($_GET['category']) ? $_GET['category'] : null ;
		
		$noteCategories = $el->student->getNoteCategories();

		if ($el->student->validNoteCategories) {
			$categories = true;

			$this->h3('Filter');
			
			$form = $this->getForm(null, array("q"=>"/modules/Students/student_view_details.php"), false, 'findNote', 'noIntBorder');
			$form->setMethod('get');
			
			$w = $form->addElement('select', 'category', $category);
			$w->addOption('');
			foreach($noteCategories as $id=>$name)
				$w->addOption($name, $id);
			$w->onChangeSubmit();
			$w->nameDisplay = 'Category';
				
			$form->addElement('hidden', 'q', "/modules/Students/student_view_details.php");
			$form->addElement('hidden', 'gibbonPersonID', $el->personID);
			$form->addElement('hidden', 'allStudents', $el->allStudents);
			$form->addElement('hidden', 'search', $el->search);
			$form->addElement('hidden', 'subpage', 'Notes');
			
			$form->render();
		}
		
		$notes = $el->student->getNotes();

		$this->linkTop(array('Add'=>array('q'=>'/modules/Students/student_view_details_notes_edit.php', 'gibbonPersonID' => $el->personID, 
			'search'=>$el->search, 'allStudents'=>$el->allStudents, 'search'=>$el->search, 'allStudents'=>$el->allStudents, 'subpage'=>'Notes',
			'category'=>$category, 'gibbonStudentNoteID'=>'Add')));

		$this->h3('Student Notes');

		if (! $el->student->validNotes) {
			$this->displayMessage('There are no records to display.');
		} else {
			$this->render('student.notes.listStart');
			
			foreach($notes as $note)
			{
				$note->categories = $noteCategories ;
				$note->search = $el->search;
				$note->allStudents = $el->allStudents;
				$this->render('student.notes.listMember', $note);
			}
			$this->render('student.notes.listEnd');

		} 
	}
}
