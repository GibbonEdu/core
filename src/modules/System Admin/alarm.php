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

namespace Module\System_Admin ;

use Gibbon\core\view ;
use Gibbon\core\trans ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'Sound Alarm';
	$trail->render($this);

    $this->render('default.flash');

	$this->h2('Sound Alarm');

	$form = $this->getForm(GIBBON_ROOT.'modules/System Admin/alarmProcess.php', array(), true, 'TheForm', true);
	
	$el = $form->addElement('audioFile', 'file', '');
	$el->injectRecord($this->config->getSetting('customAlarmSound', 'System Admin')) ;
	$el->setAudio($this);
	if (! empty($el->value)) {
		$el->description .= '<br />Will overwrite existing attachment.' ;
		$el->currentAttachment = array('Current attachment: %1$s', array(" <a href='".GIBBON_URL.'/'.$el->value."'>".$el->value.'</a><br/><br/>'));
	}
	
	$form->addElement('hidden', "attachmentCurrent", $el->value);
	
	$el = $form->addElement('select', null, null);
	$el->injectRecord($this->config->getSetting('alarm', 'System')) ;
	$el->addOption(trans::__('None'), 'None');
	$el->addOption(trans::__('General'), 'General');
	$el->addOption(trans::__('Lockdown'), 'Lockdown');
	if ($el->value === 'Custom')
		$el->addOption(trans::__('Custom'), 'Custom');

	$form->addElement("hidden", "alarmCurrent", $el->value);

	$form->addElement("submitBtn", null, 'Sound Alarm');

	$form->render();
}
