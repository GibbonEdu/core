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

if ($this->getSecurity()->isActionAccessible("/modules/School Admin/markbookSettings.php")) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'Markbook Settings';
	$trail->render($this);
	
	$this->render('default.flash');
	$this->h2 ('Markbook Settings');
	
	$form = $this->getForm(null, array('q' => 'modules/School Admin/markbookSettingsProcess.php'), true);
	$el = $form->addElement('h3', null, 'Interface Settings');
	
	$el = $form->addElement('textArea', null);
	$el->injectRecord($this->config->getSetting('markbookType', 'Markbook' ));
	$el->rows = 4;
	$el->setRequired();
	
	$el = $form->addElement('yesno', null);
	$el->injectRecord($this->config->getSetting('enableColumnWeighting', 'Markbook' ));

	$el = $form->addElement('yesno', null);
	$el->injectRecord($this->config->getSetting('enableRawAttainment', 'Markbook' ));

	$el = $form->addElement('yesno', null);
	$el->injectRecord($this->config->getSetting('enableGroupByTerm', 'Markbook' ));

	$el = $form->addElement('text', null);
	$el->injectRecord($this->config->getSetting('attainmentAlternativeName', 'Markbook' ));
	$el->setMaxLength(25);

	$el = $form->addElement('text', null);
	$el->injectRecord($this->config->getSetting('attainmentAlternativeNameAbrev', 'Markbook' ));
	$el->setMaxLength(3);

	$el = $form->addElement('text', null);
	$el->injectRecord($this->config->getSetting('effortAlternativeName', 'Markbook' ));
	$el->setMaxLength(25);

	$el = $form->addElement('text', null);
	$el->injectRecord($this->config->getSetting('effortAlternativeNameAbrev', 'Markbook' ));
	$el->setMaxLength(3);
	
	$form->addElement('h3', null, 'Warnings');

	$el = $form->addElement('yesno', null);
	$el->injectRecord($this->config->getSetting('showStudentAttainmentWarning', 'Markbook' ));

	$el = $form->addElement('yesno', null);
	$el->injectRecord($this->config->getSetting('showStudentEffortWarning', 'Markbook' ));

	$el = $form->addElement('yesno', null);
	$el->injectRecord($this->config->getSetting('showParentAttainmentWarning', 'Markbook' ));

	$el = $form->addElement('yesno', null);
	$el->injectRecord($this->config->getSetting('showParentEffortWarning', 'Markbook' ));

	$el = $form->addElement('yesno', null);
	$el->injectRecord($this->config->getSetting('personalisedWarnings', 'Markbook' ));

	$form->addElement('h3', null, 'Miscellaneous');

	$el = $form->addElement('onoff', null);
	$el->injectRecord($this->config->getSetting('wordpressCommentPush', 'Markbook' ));
	$el->addOption($this->__('On'), 'On');
	$el->addOption($this->__('Off'), 'Off');

	$form->addElement('submitBtn', null);
	$form->render();
}
