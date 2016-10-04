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
	$trail->trailEnd = 'Planner Settings';
	$trail->render($this);
	
	$this->render('default.flash');
	
	$this->h2('Planner Settings');
	
	$form = $this->getForm(GIBBON_ROOT . "modules/School Admin/plannerSettingsProcess.php", array(), true);
		
	$form->addElement('h3', null, 'Planner Templates');

	$el = $form->addElement('textArea', null);
	$el->injectRecord($this->config->getSetting('lessonDetailsTemplate', 'Planner'));
	$el->rows = 10;
	
	$el = $form->addElement('textArea', null);
	$el->injectRecord($this->config->getSetting('teachersNotesTemplate', 'Planner'));
	$el->rows = 10;

	$el = $form->addElement('textArea', null);
	$el->injectRecord($this->config->getSetting('unitOutlineTemplate', 'Planner'));
	$el->rows = 10;

	$el = $form->addElement('textArea', null);
	$el->injectRecord($this->config->getSetting('smartBlockTemplate', 'Planner'));
	$el->rows = 10;

	$form->addElement('h3', null, 'Access Settings');

	$el = $form->addElement('yesno', null);
	$el->injectRecord($this->config->getSetting('makeUnitsPublic', 'Planner'));
	$el->setRequired();

	$el = $form->addElement('yesno', null);
	$el->injectRecord($this->config->getSetting('allowOutcomeEditing', 'Planner'));
	$el->setRequired();

	$el = $form->addElement('yesno', null);
	$el->injectRecord($this->config->getSetting('sharingDefaultParents', 'Planner'));
	$el->setRequired();

	$el = $form->addElement('yesno', null);
	$el->injectRecord($this->config->getSetting('sharingDefaultStudents', 'Planner'));
	$el->setRequired();

	$form->addElement('h3', null, 'Miscellaneous');

	$el = $form->addElement('yesno', null);
	$el->injectRecord($this->config->getSetting('parentWeeklyEmailSummaryIncludeBehaviour', 'Planner'));
	$el->setRequired();

	$form->addElement('submitBtn', null);

	$form->render();	
} 