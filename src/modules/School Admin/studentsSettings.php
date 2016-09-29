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
use Gibbon\Record\studentNoteCategory;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'Manage Students Settings';
	$trail->render($this);
	
	$this->render('default.flash');

	$this->h2('Manage Students Settings');
    $this->displayMessage('This section allows you to manage the categories which can be associated with student notes. Categories can be given templates, which will pre-populate the student note on selection.', 'info');
	$this->startWell();
	$this->linkTop(array('Add'=> array('q' => '/modules/School Admin/studentsSettings_noteCategory_edit.php', 'gibbonStudentNoteCategoryID'=>'Add')));
    $this->h3('Student Note Categories');

	$dbObj = new studentNoteCategory($this);
	$categories = $dbObj->findAll('SELECT * FROM gibbonStudentNoteCategory ORDER BY name');


    if (count($categories) < 1) {
        $this->displayMessage('There are no records to display.');
    } else {
		$el = new \stdClass();
		$el->action = true;
		$this->render('studentSettings.listStart', $el);

        foreach($categories as $row) {
            $row->rowNum = NULL;
			if ($row->getField('active') == 'N') {
                $row->rowNum = 'error';
            }
			$row->action = true ;
			$this->render('studentSettings.listMember', $row);
        }
       $this->render('studentSettings.listEnd');
    }
	$this->endWell();
	

	$form = $this->getForm(null, array('q' => "modules/School Admin/studentsSettingsProcess.php"), true);
    $form->addElement('h3', null, 'Miscellaneous') ;

	$el = $form->addElement('yesno', null);
	$el->injectRecord($this->config->getSetting('enableStudentNotes', 'Students'));

	$el = $form->addElement('yesno', null);
	$el->injectRecord($this->config->getSetting('extendedBriefProfile', 'Students'));
	$el->validate = false;

	$el = $form->addElement('textArea', null);
	$el->injectRecord($this->config->getSetting('studentAgreementOptions', 'School Admin'));
	$el->rows = 4;

	$form->addElement('submitBtn', null);
	$form->render();
}
