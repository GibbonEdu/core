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
	$trail->trailEnd = 'Messenger Settings';
	$trail->render($this);
	
	$this->render('default.flash');

	$this->h2('Messenger Settings');
	$form = $this->getForm(null, array('q' => '/modules/School Admin/messengerSettingsProcess.php'), true);

	$form->addElement('h3', null, 'Message Wall Settings');

	$el = $form->addElement('select', null);
	$el->injectRecord($this->config->getSetting('messageBubbleWidthType', 'Messenger'));
	$el->value = $this->htmlPrep($el->value);
	$el->addOption($this->__('Regular'), 'Regular');
	$el->addOption($this->__('Wide'), 'Wide');

	$el = $form->addElement('rgba', null);
	$el->injectRecord($this->config->getSetting('messageBubbleBGColour', 'Messenger'));
	$el->value = $this->htmlPrep($el->value);

	$el = $form->addElement('yesno', null);
	$el->injectRecord($this->config->getSetting('messageBubbleAutoHide', 'Messenger'));

	$el = $form->addElement('yesno', null);
	$el->injectRecord($this->config->getSetting('enableHomeScreenWidget', 'Messenger'));

	$el = $form->addElement('number', null);
	$el->injectRecord($this->config->getSetting('messageRepeatTime', 'Messenger'));

	$form->addElement('submitBtn', null);
	
	$form->render();
}
